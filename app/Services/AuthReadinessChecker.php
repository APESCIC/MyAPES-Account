<?php

namespace App\Services;

use App\Exceptions\AuthReadinessException;
use App\Exceptions\DirectoryUnavailable;

class AuthReadinessChecker
{
    private const REQUIRED_GROUPS = [
        'myapes.admin',
        'myapes.staff',
        'myapes.superadmin',
        'myapes.superadmins',
    ];

    private const ALWAYS_MEMBERED_GROUPS = [
        'myapes.admin',
        'myapes.staff',
    ];

    private const SUPERADMIN_GROUPS = [
        'myapes.superadmin',
        'myapes.superadmins',
    ];

    public function __construct(
        private readonly OidcDiscoveryValidator $discovery,
        private readonly LdapGroupResolver $directory,
    ) {}

    public function check(): int
    {
        $issuer = $this->requiredString('issuer');
        $this->requiredString('client_id');
        $this->requiredString('client_secret');
        $redirectUri = $this->requiredString('redirect_uri');

        $expectedRedirect = rtrim((string) config('app.url'), '/').'/staff/auth/callback';

        if (! hash_equals($expectedRedirect, $redirectUri)) {
            throw new AuthReadinessException('oidc_configuration', 'redirect_uri_mismatch');
        }

        $scopes = config('myapes.oidc.scopes', []);

        if (! is_array($scopes) || array_diff(['openid', 'profile', 'email'], $scopes) !== []) {
            throw new AuthReadinessException('oidc_configuration', 'required_scope_missing');
        }

        $this->discovery->validate($issuer);

        $groups = $this->configuredGroups();

        if ($groups !== self::REQUIRED_GROUPS) {
            throw new AuthReadinessException('ldap_groups', 'expected_required_groups');
        }

        try {
            $catalogue = $this->directory->enumerateGroups();
        } catch (DirectoryUnavailable) {
            throw new AuthReadinessException('ldap_directory', 'unavailable');
        }

        $catalogue = collect($catalogue)->keyBy('name');

        foreach (self::ALWAYS_MEMBERED_GROUPS as $group) {
            $entry = $catalogue->get($group);

            if (! is_array($entry)) {
                throw new AuthReadinessException(
                    'ldap_groups',
                    'required_group_missing',
                );
            }

            if (($entry['member_count'] ?? 0) < 1) {
                throw new AuthReadinessException(
                    'ldap_groups',
                    'required_group_empty',
                );
            }
        }

        $superAdminMembers = 0;

        foreach (self::SUPERADMIN_GROUPS as $group) {
            $entry = $catalogue->get($group);

            if (! is_array($entry)) {
                continue;
            }

            $superAdminMembers += max(0, (int) ($entry['member_count'] ?? 0));
        }

        if ($superAdminMembers < 1) {
            throw new AuthReadinessException(
                'ldap_groups',
                'required_group_empty',
            );
        }

        return count($groups);
    }

    private function requiredString(string $key): string
    {
        $value = config("myapes.oidc.{$key}");

        if (! is_string($value) || trim($value) === '') {
            throw new AuthReadinessException('oidc_configuration', "missing_{$key}");
        }

        return trim($value);
    }

    /**
     * @return array<int, string>
     */
    private function configuredGroups(): array
    {
        $groups = (array) config('myapes.directory.required_groups', []);
        $normalized = array_values(array_unique(array_filter(array_map(
            static fn (mixed $group): string => is_string($group) ? strtolower(trim($group)) : '',
            $groups,
        ))));
        sort($normalized);

        return $normalized;
    }
}
