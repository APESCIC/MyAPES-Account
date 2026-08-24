<?php

namespace App\Services;

use App\Auth\DirectoryUserProfile;
use App\Exceptions\DirectoryIdentityNotFound;
use App\Exceptions\DirectoryUnavailable;
use App\Support\DirectoryGroupPrefix;
use Illuminate\Support\Str;

class LdapUserResolver extends LdapGroupResolver
{
    /**
     * @return array<int, DirectoryUserProfile>
     */
    public function membersOfGroup(string $groupName): array
    {
        $normalizedGroup = strtolower(trim($groupName));

        if ($normalizedGroup === ''
            || ! DirectoryGroupPrefix::isManagedGroup($normalizedGroup)) {
            return [];
        }

        $memberIdentifiers = $this->memberIdentifiersForGroup($normalizedGroup);
        $profiles = [];

        foreach ($memberIdentifiers as $identifier) {
            $profile = $this->profileForIdentifier($identifier, [$normalizedGroup]);

            if ($profile !== null) {
                $profiles[] = $profile;
            }
        }

        return $profiles;
    }

    public function profileForEmail(string $email): DirectoryUserProfile
    {
        $normalizedEmail = Str::lower(trim($email));

        if ($normalizedEmail === '') {
            throw new DirectoryIdentityNotFound('No LDAP identity matched the authenticated user.');
        }

        $groups = $this->resolveByEmail($normalizedEmail);
        $profile = $this->profileForIdentifier($normalizedEmail, $groups, byEmail: true);

        if ($profile === null) {
            throw new DirectoryIdentityNotFound('No LDAP identity matched the authenticated user.');
        }

        return $profile;
    }

    /**
     * @return array<int, string>
     */
    private function memberIdentifiersForGroup(string $groupName): array
    {
        $config = $this->configuration();
        $groupsBaseDn = $this->requiredString($config, 'groups_base_dn');
        $filter = '(&(objectclass=group)(cn='.ldap_escape(
            $groupName,
            '',
            LDAP_ESCAPE_FILTER,
        ).'))';
        $entries = $this->successfulEntries(
            $this->fetchGroupEntries(
                $config,
                $groupsBaseDn,
                $filter,
                ['memberuid', 'member'],
            ),
        );

        if ((int) ($entries['count'] ?? 0) !== 1) {
            return [];
        }

        $entry = $entries[0];
        $identifiers = [];

        $memberUids = $entry['memberuid'] ?? null;

        if (is_array($memberUids)) {
            for ($i = 0; $i < (int) ($memberUids['count'] ?? 0); $i++) {
                if (isset($memberUids[$i]) && is_string($memberUids[$i])) {
                    $identifiers[] = strtolower(trim($memberUids[$i]));
                }
            }
        }

        $members = $entry['member'] ?? null;

        if (is_array($members)) {
            for ($i = 0; $i < (int) ($members['count'] ?? 0); $i++) {
                if (! isset($members[$i]) || ! is_string($members[$i])) {
                    continue;
                }

                $uid = $this->uidFromDn($members[$i]);

                if ($uid !== null) {
                    $identifiers[] = $uid;
                }
            }
        }

        return array_values(array_unique(array_filter($identifiers)));
    }

    /**
     * @param  array<int, string>  $groups
     */
    private function profileForIdentifier(
        string $identifier,
        array $groups,
        bool $byEmail = false,
    ): ?DirectoryUserProfile {
        $config = $this->configuration();
        $baseDn = $this->requiredString($config, 'base_dn');
        $filter = $byEmail
            ? sprintf(
                (string) ($config['user_filter'] ?? '(mail=%s)'),
                ldap_escape($identifier, '', LDAP_ESCAPE_FILTER),
            )
            : '(uid='.ldap_escape($identifier, '', LDAP_ESCAPE_FILTER).')';
        $entries = $this->successfulEntries(
            $this->fetchSearchEntries(
                $config,
                $baseDn,
                $filter,
                ['mail', 'cn', 'displayname', 'title', 'telephonenumber', 'mobile'],
            ),
        );

        if ((int) ($entries['count'] ?? 0) !== 1) {
            return null;
        }

        $entry = $entries[0];
        $email = $this->firstString($entry['mail'] ?? null);

        if ($email === null) {
            return null;
        }

        $email = Str::lower(trim($email));
        $name = $this->firstString($entry['displayname'] ?? null)
            ?? $this->firstString($entry['cn'] ?? null)
            ?? $email;
        $jobTitle = $this->firstString($entry['title'] ?? null);
        $workPhone = $this->firstString($entry['telephonenumber'] ?? null)
            ?? $this->firstString($entry['mobile'] ?? null);

        if ($byEmail) {
            $groups = $this->resolveByEmail($email);
        }

        return new DirectoryUserProfile(
            email: $email,
            name: $name,
            jobTitle: $jobTitle,
            workPhone: $workPhone,
            groups: DirectoryGroupPrefix::filterGroups($groups),
        );
    }

    private function uidFromDn(string $dn): ?string
    {
        foreach (explode(',', $dn) as $piece) {
            $piece = trim($piece);

            if (str_starts_with(strtolower($piece), 'uid=')) {
                $uid = substr($piece, 4);

                return $uid !== '' ? strtolower(trim($uid)) : null;
            }
        }

        return null;
    }

    private function firstString(mixed $value): ?string
    {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        if (is_array($value) && isset($value[0]) && is_string($value[0])) {
            $string = trim($value[0]);

            return $string !== '' ? $string : null;
        }

        return null;
    }
}
