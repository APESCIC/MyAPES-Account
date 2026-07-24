<?php

namespace App\Services;

use RuntimeException;

class LdapGroupResolver
{
    /**
     * @return array<int, string>
     */
    public function resolveByEmail(string $email): array
    {
        if (! function_exists('ldap_connect')) {
            throw new RuntimeException('LDAP PHP extension is not installed.');
        }

        $config = config('myapes.ldap');

        $host = $config['host'] ?? null;
        $baseDn = $config['base_dn'] ?? null;
        $bindDn = $config['bind_dn'] ?? null;
        $bindPassword = $config['bind_password'] ?? null;
        $userFilter = $config['user_filter'] ?? '(mail=%s)';
        $groupAttribute = $config['group_attribute'] ?? 'memberOf';

        if (! is_string($host) || ! is_string($baseDn) || ! is_string($bindDn) || ! is_string($bindPassword)) {
            throw new RuntimeException('LDAP configuration is incomplete.');
        }

        $ldap = ldap_connect($host, (int) ($config['port'] ?? 389));

        if ($ldap === false) {
            throw new RuntimeException('Unable to connect to LDAP host.');
        }

        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

        if (($config['start_tls'] ?? false) === true && ! ldap_start_tls($ldap)) {
            throw new RuntimeException('LDAP STARTTLS negotiation failed.');
        }

        if (! @ldap_bind($ldap, $bindDn, $bindPassword)) {
            throw new RuntimeException('LDAP bind failed.');
        }

        $escapedEmail = ldap_escape($email, '', LDAP_ESCAPE_FILTER);
        $filter = sprintf($userFilter, $escapedEmail);
        $search = ldap_search($ldap, $baseDn, $filter, [$groupAttribute]);

        if ($search === false) {
            throw new RuntimeException('LDAP user search failed.');
        }

        $entries = ldap_get_entries($ldap, $search);

        if (! is_array($entries) || ($entries['count'] ?? 0) < 1) {
            throw new RuntimeException('No LDAP entry found for authenticated user.');
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
    }

    private function normalizeGroupName(string $group): string
    {
        $pieces = explode(',', $group);
        $cn = $pieces[0] ?? $group;

        return strtolower(str_replace('cn=', '', trim($cn)));
    }
}
