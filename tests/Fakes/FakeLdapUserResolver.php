<?php

namespace Tests\Fakes;

use App\Auth\DirectoryUserProfile;
use App\Exceptions\DirectoryIdentityNotFound;
use App\Services\LdapUserResolver;
use App\Support\DirectoryGroupPrefix;
use Illuminate\Support\Str;
use Throwable;

final class FakeLdapUserResolver extends LdapUserResolver
{
    /**
     * @var array<int, string>
     */
    public array $groups = [];

    public ?Throwable $failure = null;

    /**
     * @var array<int, string>
     */
    public array $resolvedEmails = [];

    /**
     * @var array<string, array<int, DirectoryUserProfile>>
     */
    public array $membersByGroup = [];

    public function profileForEmail(string $email): DirectoryUserProfile
    {
        $normalizedEmail = Str::lower(trim($email));
        $this->resolvedEmails[] = $normalizedEmail;

        if ($this->failure !== null) {
            throw $this->failure;
        }

        $groups = DirectoryGroupPrefix::filterGroups($this->groups);

        if ($groups === []) {
            throw new DirectoryIdentityNotFound(
                'No LDAP identity matched the authenticated user.',
            );
        }

        return new DirectoryUserProfile(
            email: $normalizedEmail,
            name: 'Directory Test User',
            jobTitle: 'Coordinator',
            workPhone: null,
            groups: $groups,
        );
    }

    /**
     * @return array<int, DirectoryUserProfile>
     */
    public function membersOfGroup(string $groupName): array
    {
        $normalizedGroup = strtolower(trim($groupName));

        return $this->membersByGroup[$normalizedGroup] ?? [];
    }
}
