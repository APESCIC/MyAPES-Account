<?php

namespace App\Services;

use App\Auth\DirectoryUserProfile;
use App\Models\User;
use App\Support\DirectoryGroupPrefix;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DirectoryUserSynchronizer
{
    public function __construct(
        private readonly LdapUserResolver $directory,
        private readonly DirectoryRoleSynchronizer $roles,
        private readonly StaffProfileDirectorySynchronizer $staffProfiles,
    ) {}

    /**
     * @return array{seen: int, created: int, updated: int}
     */
    public function synchronize(): array
    {
        $profiles = $this->collectProfiles();
        $seen = count($profiles);
        $created = 0;
        $updated = 0;

        foreach ($profiles as $profile) {
            $wasCreated = DB::transaction(function () use ($profile): bool {
                return $this->synchronizeProfile($profile);
            });

            if ($wasCreated) {
                $created++;
            } else {
                $updated++;
            }
        }

        return [
            'seen' => $seen,
            'created' => $created,
            'updated' => $updated,
        ];
    }

    /**
     * @return array<string, DirectoryUserProfile>
     */
    private function collectProfiles(): array
    {
        $profiles = [];

        foreach (DirectoryGroupPrefix::requiredGroups() as $groupName) {
            foreach ($this->directory->membersOfGroup($groupName) as $profile) {
                $email = Str::lower(trim($profile->email));

                if ($email === '') {
                    continue;
                }

                if (! isset($profiles[$email])) {
                    $profiles[$email] = $profile;

                    continue;
                }

                $existing = $profiles[$email];
                $mergedGroups = DirectoryGroupPrefix::filterGroups(array_merge(
                    $existing->groups,
                    $profile->groups,
                    [$groupName],
                ));

                $profiles[$email] = new DirectoryUserProfile(
                    email: $email,
                    name: $existing->name !== '' ? $existing->name : $profile->name,
                    jobTitle: $existing->jobTitle ?? $profile->jobTitle,
                    workPhone: $existing->workPhone ?? $profile->workPhone,
                    groups: $mergedGroups,
                );
            }
        }

        return $profiles;
    }

    private function synchronizeProfile(DirectoryUserProfile $profile): bool
    {
        $email = Str::lower(trim($profile->email));
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($user !== null && $user->identity_type === User::IDENTITY_LOCAL) {
            return false;
        }

        $created = false;

        if ($user === null) {
            $user = new User;
            $user->identity_type = User::IDENTITY_CLOUDRON_OIDC;
            $user->email = $email;
            $user->email_verified_at = now();
            $created = true;
        }

        $user->name = $profile->name !== '' ? $profile->name : $email;
        $user->save();

        $this->staffProfiles->apply($user, $profile);
        $this->roles->synchronize($user, $profile->groups, false);

        return $created;
    }
}
