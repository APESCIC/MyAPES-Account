<?php

namespace App\Services;

use App\Auth\DirectoryUserProfile;
use App\Models\User;
use App\Support\DirectoryGroupPrefix;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DirectoryUserSynchronizer
{
    public const SUSPENSION_REASON_DIRECTORY_DISABLED = 'directory-disabled';

    public function __construct(
        private readonly LdapUserResolver $directory,
        private readonly DirectoryRoleSynchronizer $roles,
        private readonly StaffProfileDirectorySynchronizer $staffProfiles,
    ) {}

    /**
     * @return array{
     *     seen: int,
     *     created: int,
     *     updated: int,
     *     suspended: int,
     *     unsuspended: int
     * }
     */
    public function synchronize(): array
    {
        $profiles = $this->collectProfiles();
        $seenEmails = array_keys($profiles);
        $seen = count($profiles);
        $created = 0;
        $updated = 0;
        $unsuspended = 0;

        foreach ($profiles as $profile) {
            $outcome = DB::transaction(function () use ($profile): string {
                return $this->synchronizeProfile($profile);
            });

            match ($outcome) {
                'created' => $created++,
                'updated' => $updated++,
                'unsuspended' => $unsuspended++,
                default => null,
            };
        }

        $suspended = $this->suspendMissingDirectoryUsers($seenEmails);

        return [
            'seen' => $seen,
            'created' => $created,
            'updated' => $updated,
            'suspended' => $suspended,
            'unsuspended' => $unsuspended,
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

    /**
     * @return 'created'|'updated'|'unsuspended'|'skipped'
     */
    private function synchronizeProfile(DirectoryUserProfile $profile): string
    {
        $email = Str::lower(trim($profile->email));
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->lockForUpdate()
            ->first();

        if ($user !== null && $user->identity_type === User::IDENTITY_LOCAL) {
            return 'skipped';
        }

        $created = false;

        if ($user === null) {
            $user = new User;
            $user->identity_type = User::IDENTITY_CLOUDRON_OIDC;
            $user->email = $email;
            $user->email_verified_at = now();
            $created = true;
        }

        $clearedDirectorySuspension = $this->clearDirectoryDisabledSuspension($user);

        $user->name = $profile->name !== '' ? $profile->name : $email;
        $user->save();

        $this->staffProfiles->apply($user, $profile);
        $this->roles->synchronize($user, $profile->groups, false);

        if ($created) {
            return 'created';
        }

        return $clearedDirectorySuspension ? 'unsuspended' : 'updated';
    }

    /**
     * @param  array<int, string>  $seenEmails
     */
    private function suspendMissingDirectoryUsers(array $seenEmails): int
    {
        $seenLookup = [];

        foreach ($seenEmails as $email) {
            $seenLookup[Str::lower(trim($email))] = true;
        }

        $candidates = User::query()
            ->where('identity_type', User::IDENTITY_CLOUDRON_OIDC)
            ->orderBy('id')
            ->get();

        $suspended = 0;

        foreach ($candidates as $candidate) {
            $email = Str::lower(trim((string) $candidate->email));

            if ($email === '' || isset($seenLookup[$email])) {
                continue;
            }

            $didSuspend = DB::transaction(function () use ($candidate): bool {
                $user = User::query()
                    ->whereKey($candidate->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($user === null
                    || $user->identity_type !== User::IDENTITY_CLOUDRON_OIDC) {
                    return false;
                }

                return $this->markDirectoryDisabled($user);
            });

            if ($didSuspend) {
                $suspended++;
            }
        }

        return $suspended;
    }

    private function clearDirectoryDisabledSuspension(User $user): bool
    {
        if ($user->suspended_at === null
            || $user->suspension_reason !== self::SUSPENSION_REASON_DIRECTORY_DISABLED) {
            return false;
        }

        $user->forceFill([
            'suspended_at' => null,
            'suspended_by' => null,
            'suspension_reason' => null,
            'authorization_epoch' => (int) $user->authorization_epoch + 1,
        ]);
        $user->setRememberToken(Str::random(60));

        return true;
    }

    private function markDirectoryDisabled(User $user): bool
    {
        $this->roles->revoke($user);

        if ($user->suspended_at !== null) {
            return false;
        }

        $user->forceFill([
            'suspended_at' => now(),
            'suspended_by' => null,
            'suspension_reason' => self::SUSPENSION_REASON_DIRECTORY_DISABLED,
            'authorization_epoch' => (int) $user->authorization_epoch + 1,
        ]);
        $user->setRememberToken(Str::random(60));
        $user->save();

        return true;
    }
}
