<?php

namespace App\Services;

use App\Exceptions\DirectoryIdentityNotFound;
use App\Exceptions\DirectoryUnavailable;
use App\Support\DirectoryGroupPrefix;

class LdapGroupResolver
{
    private const LDAP_SUCCESS = 0;

    /**
     * @return array<int, array{
     *     name: string,
     *     external_id: ?string,
     *     member_count: int
     * }>
     */
    public function enumerateGroups(): array
    {
        $config = $this->configuration();
        $groupsBaseDn = $this->requiredString($config, 'groups_base_dn');
        $entries = $this->successfulEntries(
            $this->fetchGroupEntries(
                $config,
                $groupsBaseDn,
                '(objectclass=group)',
                ['cn', 'gidnumber', 'memberuid'],
            ),
        );
        $groups = [];

        for ($i = 0; $i < (int) ($entries['count'] ?? 0); $i++) {
            $entry = $entries[$i] ?? null;
            $name = is_array($entry) ? ($entry['cn'][0] ?? null) : null;

            if (! is_string($name) || trim($name) === '') {
                throw new DirectoryUnavailable(
                    'LDAP group search returned an invalid result.',
                );
            }

            $externalId = $entry['gidnumber'][0] ?? null;
            $externalId = is_string($externalId) && trim($externalId) !== ''
                ? trim($externalId)
                : null;
            $members = $entry['memberuid'] ?? null;

            $groups[] = [
                'name' => strtolower(trim($name)),
                'external_id' => $externalId,
                'member_count' => is_array($members)
                    ? max(0, (int) ($members['count'] ?? 0))
                    : 0,
            ];
        }

        usort(
            $groups,
            static fn (array $left, array $right): int => $left['name'] <=> $right['name'],
        );

        return array_values(array_filter(
            $groups,
            static fn (array $group): bool => DirectoryGroupPrefix::isManagedGroup(
                $group['name'],
            ),
        ));
    }

    /**
     * @return array<int, string>
     */
    public function resolveByEmail(string $email): array
    {
        $config = $this->configuration();
        $baseDn = $this->requiredString($config, 'base_dn');
        $userFilter = $config['user_filter'] ?? '(mail=%s)';
        $groupAttribute = $config['group_attribute'] ?? 'memberOf';

        if (! is_string($userFilter) || ! str_contains($userFilter, '%s')) {
            throw new DirectoryUnavailable('LDAP user filter is invalid.');
        }

        if (! is_string($groupAttribute) || trim($groupAttribute) === '') {
            throw new DirectoryUnavailable('LDAP group attribute is invalid.');
        }

        $escapedEmail = ldap_escape($email, '', LDAP_ESCAPE_FILTER);
        $filter = sprintf($userFilter, $escapedEmail);
        $entries = $this->successfulEntries(
            $this->fetchSearchEntries(
                $config,
                $baseDn,
                $filter,
                [$groupAttribute],
            ),
        );
        $entryCount = (int) ($entries['count'] ?? 0);

        if ($entryCount === 0) {
            throw new DirectoryIdentityNotFound('No LDAP identity matched the authenticated user.');
        }

        if ($entryCount !== 1) {
            throw new DirectoryUnavailable('LDAP identity search returned an ambiguous result.');
        }

        $entry = $entries[0];
        $values = $entry[strtolower($groupAttribute)] ?? null;

        if (! is_array($values) || ($values['count'] ?? 0) === 0) {
            return [];
        }

        $groups = [];

        for ($i = 0; $i < (int) $values['count']; $i++) {
            if (isset($values[$i]) && is_string($values[$i])) {
                $groups[] = $this->normalizeGroupName($values[$i]);
            }
        }

        return DirectoryGroupPrefix::filterGroups(
            array_values(array_unique($groups)),
        );
    }

    /**
     * @param  array<int, string>  $groupNames
     * @return array<int, string>
     */
    public function existingGroups(array $groupNames): array
    {
        $expected = array_values(array_unique(array_filter(array_map(
            static fn (string $group): string => strtolower(trim($group)),
            $groupNames,
        ))));

        if ($expected === []) {
            return [];
        }

        $config = $this->configuration();
        $groupsBaseDn = $this->requiredString($config, 'groups_base_dn');
        $clauses = array_map(
            static fn (string $group): string => '(cn='.ldap_escape($group, '', LDAP_ESCAPE_FILTER).')',
            $expected,
        );
        $filter = '(&(objectclass=group)(|'.implode('', $clauses).'))';
        $entries = $this->successfulEntries(
            $this->fetchSearchEntries(
                $config,
                $groupsBaseDn,
                $filter,
                ['cn'],
            ),
        );
        $existing = [];

        for ($i = 0; $i < (int) ($entries['count'] ?? 0); $i++) {
            $cn = $entries[$i]['cn'][0] ?? null;

            if (is_string($cn) && trim($cn) !== '') {
                $existing[] = strtolower(trim($cn));
            }
        }

        return array_values(array_unique($existing));
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, string>  $attributes
     * @return array<string|int, mixed>
     */
    protected function fetchGroupEntries(
        array $config,
        string $baseDn,
        string $filter,
        array $attributes,
    ): array {
        return $this->fetchSearchEntries(
            $config,
            $baseDn,
            $filter,
            $attributes,
        );
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, string>  $attributes
     * @return array<string|int, mixed>
     */
    protected function fetchSearchEntries(
        array $config,
        string $baseDn,
        string $filter,
        array $attributes,
    ): array {
        $ldap = $this->connect($config);

        try {
            $search = @ldap_search(
                $ldap,
                $baseDn,
                $filter,
                $attributes,
                0,
                0,
                (int) ($config['search_timeout_seconds'] ?? 10),
            );

            if ($search === false) {
                throw new DirectoryUnavailable('LDAP group search failed.');
            }

            $resultCode = null;

            if (! @ldap_parse_result($ldap, $search, $resultCode)
                || ! is_int($resultCode)) {
                throw new DirectoryUnavailable(
                    'LDAP search result could not be verified.',
                );
            }

            if ($resultCode !== self::LDAP_SUCCESS) {
                return [
                    'count' => 0,
                    'result_code' => $resultCode,
                ];
            }

            $entries = ldap_get_entries($ldap, $search);

            if (! is_array($entries)) {
                throw new DirectoryUnavailable(
                    'LDAP group search returned an invalid result.',
                );
            }

            $entries['result_code'] = $resultCode;

            return $entries;
        } finally {
            $this->unbindConnection($ldap);
        }
    }

    /**
     * @param  array<string|int, mixed>  $result
     * @return array<string|int, mixed>
     */
    protected function successfulEntries(array $result): array
    {
        $resultCode = $result['result_code'] ?? null;

        if (! is_int($resultCode)
            || $resultCode !== self::LDAP_SUCCESS) {
            throw new DirectoryUnavailable(
                'LDAP search did not complete successfully.',
            );
        }

        unset($result['result_code']);

        return $result;
    }

    private function normalizeGroupName(string $group): string
    {
        $pieces = explode(',', $group);
        $cn = $pieces[0] ?? $group;

        $normalized = preg_replace('/^cn=/i', '', trim($cn));

        return strtolower((string) $normalized);
    }

    /**
     * @return array<string, mixed>
     */
    protected function configuration(): array
    {
        if (! function_exists('ldap_connect')) {
            throw new DirectoryUnavailable('LDAP PHP extension is not installed.');
        }

        $config = config('myapes.ldap');

        if (! is_array($config)) {
            throw new DirectoryUnavailable('LDAP configuration is incomplete.');
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function connect(array $config): mixed
    {
        $host = $this->requiredString($config, 'host');
        $bindDn = $this->requiredString($config, 'bind_dn');
        $bindPassword = $this->requiredString($config, 'bind_password');
        $ldap = $this->openConnection(
            $host,
            (int) ($config['port'] ?? 389),
        );

        if ($ldap === false) {
            throw new DirectoryUnavailable('Unable to connect to LDAP.');
        }

        if (! $this->applyConnectionOption(
            $ldap,
            LDAP_OPT_PROTOCOL_VERSION,
            3,
        )
            || ! $this->applyConnectionOption(
                $ldap,
                LDAP_OPT_REFERRALS,
                0,
            )
            || ! $this->applyConnectionOption(
                $ldap,
                LDAP_OPT_NETWORK_TIMEOUT,
                (int) ($config['connect_timeout_seconds'] ?? 5),
            )) {
            $this->unbindConnection($ldap);

            throw new DirectoryUnavailable(
                'LDAP connection options could not be applied.',
            );
        }

        if (($config['start_tls'] ?? false) === true && ! @ldap_start_tls($ldap)) {
            $this->unbindConnection($ldap);
            throw new DirectoryUnavailable('LDAP STARTTLS negotiation failed.');
        }

        if (! @ldap_bind($ldap, $bindDn, $bindPassword)) {
            $this->unbindConnection($ldap);
            throw new DirectoryUnavailable('LDAP bind failed.');
        }

        return $ldap;
    }

    protected function openConnection(string $host, int $port): mixed
    {
        return @ldap_connect($host, $port);
    }

    protected function applyConnectionOption(
        mixed $connection,
        int $option,
        mixed $value,
    ): bool {
        return @ldap_set_option($connection, $option, $value);
    }

    protected function unbindConnection(mixed $connection): void
    {
        @ldap_unbind($connection);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected function requiredString(array $config, string $key): string
    {
        $value = $config[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw new DirectoryUnavailable("LDAP {$key} is not configured.");
        }

        return trim($value);
    }
}
