<?php

namespace App\Services;

use App\Auth\DirectoryAuthorizationResult;
use App\Models\DirectoryGroup;
use App\Models\Role;
use App\Models\RoleSource;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DirectoryRoleSynchronizer
{
    public function __construct(
        private readonly AuthorizationProfile $profile,
        private readonly AuthorizationRoleMaterializer $materializer,
        private readonly LegacyAccessCompatibilityAdapter $legacy,
    ) {}

    /**
     * @param  array<int, string>  $groups
     */
    public function protectedRoleForGroups(array $groups): ?string
    {
        $mappedRoleNames = $this->mappings($groups)
            ->pluck('role_name')
            ->unique()
            ->all();

        foreach ($this->profile->protectedRolesByPrecedence() as $roleName) {
            if ($roleName !== AuthorizationProfile::ROLE_SERVICE_USER
                && in_array($roleName, $mappedRoleNames, true)) {
                return $roleName;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $groups
     */
    public function synchronize(
        User $user,
        array $groups,
        bool $invalidateSession = true,
    ): DirectoryAuthorizationResult {
        return DB::transaction(function () use (
            $user,
            $groups,
            $invalidateSession,
        ): DirectoryAuthorizationResult {
            $this->lockUser($user);
            $user->refresh();
            $newUser = $user->wasRecentlyCreated;
            $previousProtectedRole = $this->profile
                ->effectiveProtectedRole($user);
            $beforeRoleIds = $this->effectiveRoleIds($user);
            $hadDirectorySources = RoleSource::query()
                ->whereBelongsTo($user)
                ->where('source', RoleSource::SOURCE_DIRECTORY)
                ->exists();
            $normalizedGroups = $this->normalizeGroups($groups);
            $mappings = $this->mappings($normalizedGroups);
            $protectedRole = $this->protectedRoleForMappings($mappings);

            if ($protectedRole === null) {
                $this->removeDirectorySources($user, collect());
                $this->writeCompatibilityMirror(
                    $user,
                    AuthorizationProfile::ROLE_SERVICE_USER,
                    [],
                );
            } else {
                $desiredMappings = $mappings->filter(
                    fn (object $mapping): bool => $mapping->role_name === $protectedRole
                        || ! $this->profile->isProtectedRole($mapping->role_name),
                );
                $this->removeDirectorySources($user, $desiredMappings);
                $this->grantDirectorySources($user, $desiredMappings);
                $this->writeCompatibilityMirror(
                    $user,
                    $protectedRole,
                    $normalizedGroups,
                );
            }

            $user->refresh();
            $afterRoleIds = $this->effectiveRoleIds($user);
            $authorizationChanged = $beforeRoleIds !== $afterRoleIds
                || ($protectedRole === null && $hadDirectorySources);

            if ($authorizationChanged && ! $newUser && $invalidateSession) {
                $this->invalidateRememberedAuthorization($user);
            }

            return new DirectoryAuthorizationResult(
                eligible: $protectedRole !== null,
                protectedRole: $protectedRole,
                previousProtectedRole: $previousProtectedRole,
                authorizationChanged: $authorizationChanged,
            );
        });
    }

    public function revoke(User $user): DirectoryAuthorizationResult
    {
        return $this->synchronize($user, []);
    }

    /**
     * @param  array<int, string>  $groups
     * @return Collection<int, object{
     *     group_id: int,
     *     group_name: string,
     *     role_id: int,
     *     role_name: string
     * }>
     */
    private function mappings(array $groups): Collection
    {
        $normalized = $this->normalizeGroups($groups);

        if ($normalized === []) {
            return collect();
        }

        $query = DB::table('directory_group_role_mappings')
            ->join(
                'directory_groups',
                'directory_groups.id',
                '=',
                'directory_group_role_mappings.directory_group_id',
            )
            ->join(
                'roles',
                'roles.id',
                '=',
                'directory_group_role_mappings.role_id',
            )
            ->whereIn('directory_groups.name', $normalized)
            ->where('roles.guard_name', 'web');

        if (Schema::hasColumn('directory_groups', 'app_enabled')) {
            $query->where('directory_groups.app_enabled', true);
        }

        return $query->get([
            'directory_groups.id as group_id',
            'directory_groups.name as group_name',
            'roles.id as role_id',
            'roles.name as role_name',
        ]);
    }

    /**
     * @param  Collection<int, object>  $mappings
     */
    private function protectedRoleForMappings(Collection $mappings): ?string
    {
        $mappedRoleNames = $mappings->pluck('role_name')->unique()->all();

        foreach ($this->profile->protectedRolesByPrecedence() as $roleName) {
            if ($roleName !== AuthorizationProfile::ROLE_SERVICE_USER
                && in_array($roleName, $mappedRoleNames, true)) {
                return $roleName;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, object>  $desiredMappings
     */
    private function removeDirectorySources(
        User $user,
        Collection $desiredMappings,
    ): void {
        $desired = $desiredMappings
            ->map(
                static fn (object $mapping): string => $mapping->role_id.':'.$mapping->group_id,
            )
            ->all();

        $existing = RoleSource::query()
            ->with(['role', 'directoryGroup'])
            ->whereBelongsTo($user)
            ->where('source', RoleSource::SOURCE_DIRECTORY)
            ->get();

        foreach ($existing as $source) {
            $key = $source->role_id.':'.$source->directory_group_id;

            if (in_array($key, $desired, true)) {
                continue;
            }

            $this->materializer->revoke(
                $user,
                $source->getRelation('role'),
                RoleSource::SOURCE_DIRECTORY,
                $source->directoryGroup,
            );
        }
    }

    /**
     * @param  Collection<int, object>  $desiredMappings
     */
    private function grantDirectorySources(
        User $user,
        Collection $desiredMappings,
    ): void {
        $roles = Role::query()
            ->whereIn('id', $desiredMappings->pluck('role_id'))
            ->get()
            ->keyBy('id');
        $groups = DirectoryGroup::query()
            ->whereIn('id', $desiredMappings->pluck('group_id'))
            ->get()
            ->keyBy('id');

        foreach ($desiredMappings as $mapping) {
            $this->materializer->grant(
                $user,
                $roles->get($mapping->role_id),
                RoleSource::SOURCE_DIRECTORY,
                $groups->get($mapping->group_id),
            );
        }
    }

    /**
     * @param  array<int, string>  $groups
     */
    private function writeCompatibilityMirror(
        User $user,
        string $protectedRole,
        array $groups,
    ): void {
        $this->legacy->write(
            $user,
            $this->profile->legacyAccessLevelFor($protectedRole),
        );
        $user->forceFill([
            'ldap_groups' => $groups,
        ])->save();
    }

    private function invalidateRememberedAuthorization(User $user): void
    {
        $user->forceFill([
            'authorization_epoch' => $user->authorization_epoch + 1,
        ]);
        $user->setRememberToken(Str::random(60));
        $user->save();
    }

    /**
     * @return array<int, int>
     */
    private function effectiveRoleIds(User $user): array
    {
        $ids = $user->roles()->pluck('roles.id')->map(
            static fn (mixed $id): int => (int) $id,
        )->all();
        sort($ids);

        return $ids;
    }

    /**
     * @param  array<int, string>  $groups
     * @return array<int, string>
     */
    private function normalizeGroups(array $groups): array
    {
        $normalized = array_values(array_unique(array_filter(array_map(
            static fn (mixed $group): string => is_string($group)
                ? strtolower(trim($group))
                : '',
            $groups,
        ))));
        sort($normalized);

        return $normalized;
    }

    private function lockUser(User $user): void
    {
        DB::table($user->getTable())
            ->where($user->getKeyName(), $user->getKey())
            ->lockForUpdate()
            ->first();
    }
}
