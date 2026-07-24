<?php

namespace App\Services;

use App\Exceptions\AuthReadinessException;
use App\Exceptions\DirectoryUnavailable;

class AuthReadinessChecker
{
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

        if (count($groups) !== 5) {
            throw new AuthReadinessException('ldap_groups', 'expected_five_groups');
        }

        try {
            $existing = $this->directory->existingGroups($groups);
        } catch (DirectoryUnavailable) {
            throw new AuthReadinessException('ldap_directory', 'unavailable');
        }

        $missing = array_diff($groups, $existing);

        if ($missing !== []) {
            throw new AuthReadinessException('ldap_groups', 'configured_group_missing');
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
        $groups = array_merge(
            (array) config('myapes.roles.staff_groups', []),
            (array) config('myapes.roles.admin_groups', []),
            (array) config('myapes.roles.superadmin_groups', []),
        );
        $normalized = array_values(array_unique(array_filter(array_map(
            static fn (mixed $group): string => is_string($group) ? strtolower(trim($group)) : '',
            $groups,
        ))));
        sort($normalized);

        return $normalized;
    }
}
