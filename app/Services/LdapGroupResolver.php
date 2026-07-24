<?php

namespace App\Services;

use App\Exceptions\DirectoryIdentityNotFound;
use App\Exceptions\DirectoryUnavailable;

class LdapGroupResolver
{
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

        $ldap = $this->connect($config);

        try {
            $escapedEmail = ldap_escape($email, '', LDAP_ESCAPE_FILTER);
            $filter = sprintf($userFilter, $escapedEmail);
            $search = @ldap_search($ldap, $baseDn, $filter, [$groupAttribute]);

            if ($search === false) {
                throw new DirectoryUnavailable('LDAP user search failed.');
            }

            $entries = ldap_get_entries($ldap, $search);
            $entryCount = is_array($entries) ? (int) ($entries['count'] ?? 0) : 0;

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

            return array_values(array_unique($groups));
        } finally {
            @ldap_unbind($ldap);
        }
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
        $ldap = $this->connect($config);

        try {
            $clauses = array_map(
                static fn (string $group): string => '(cn='.ldap_escape($group, '', LDAP_ESCAPE_FILTER).')',
                $expected,
            );
            $filter = '(&(objectclass=group)(|'.implode('', $clauses).'))';
            $search = @ldap_search($ldap, $groupsBaseDn, $filter, ['cn']);

            if ($search === false) {
                throw new DirectoryUnavailable('LDAP group search failed.');
            }

            $entries = ldap_get_entries($ldap, $search);

            if (! is_array($entries)) {
                throw new DirectoryUnavailable('LDAP group search returned an invalid result.');
            }

            $existing = [];

            for ($i = 0; $i < (int) ($entries['count'] ?? 0); $i++) {
                $cn = $entries[$i]['cn'][0] ?? null;

                if (is_string($cn) && trim($cn) !== '') {
                    $existing[] = strtolower(trim($cn));
                }
            }

            return array_values(array_unique($existing));
        } finally {
            @ldap_unbind($ldap);
        }
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
    private function configuration(): array
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
        $ldap = @ldap_connect($host, (int) ($config['port'] ?? 389));

        if ($ldap === false) {
            throw new DirectoryUnavailable('Unable to connect to LDAP.');
        }

        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

        if (($config['start_tls'] ?? false) === true && ! @ldap_start_tls($ldap)) {
            @ldap_unbind($ldap);
            throw new DirectoryUnavailable('LDAP STARTTLS negotiation failed.');
        }

        if (! @ldap_bind($ldap, $bindDn, $bindPassword)) {
            @ldap_unbind($ldap);
            throw new DirectoryUnavailable('LDAP bind failed.');
        }

        return $ldap;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function requiredString(array $config, string $key): string
    {
        $value = $config[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw new DirectoryUnavailable("LDAP {$key} is not configured.");
        }

        return trim($value);
    }
}
