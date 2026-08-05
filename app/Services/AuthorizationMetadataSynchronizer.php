<?php

namespace App\Services;

use App\Exceptions\AuthorizationLifecycleException;
use App\Models\DirectoryGroup;
use App\Models\DirectoryGroupRoleMapping;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class AuthorizationMetadataSynchronizer
{
    private const LEGACY_ALIASES = [
        'position.staff',
        'position.students',
        'position.volunteers',
        'intranet.administrator',
        'intranet.superadmin',
    ];

    private const IMMUTABLE_MAPPINGS = [
        'myapes.staff' => AuthorizationProfile::ROLE_STAFF,
        'myapes.admin' => AuthorizationProfile::ROLE_ADMINISTRATOR,
        'myapes.superadmin' => AuthorizationProfile::ROLE_SUPER_ADMIN,
    ];

    public function __construct(
        private readonly AuthorizationProfile $profile,
        private readonly AuthorizationPermissionSynchronizer $permissions,
    ) {}

    public function synchronize(): void
    {
        $this->synchronizeRoles();
        $this->synchronizePermissions();
        $this->synchronizeMappings();
    }

    private function synchronizeRoles(): void
    {
        foreach ($this->profile->protectedRolesByPrecedence() as $name) {
            $role = Role::query()
                ->where('guard_name', 'web')
                ->where('name', $name)
                ->first();

            if ($role === null) {
                $role = new Role;
                $role->forceFill([
                    'name' => $name,
                    'guard_name' => 'web',
                    'is_protected' => true,
                ])->save();
            }

            if (! $role->is_protected) {
                throw new AuthorizationLifecycleException('protected_roles');
            }
        }

        if (Role::query()
            ->where('guard_name', 'web')
            ->where('is_protected', true)
            ->whereNotIn('name', $this->profile->protectedRolesByPrecedence())
            ->exists()) {
            throw new AuthorizationLifecycleException('protected_roles');
        }
    }

    private function synchronizePermissions(): void
    {
        $expected = $this->profile->permissions();

        foreach ($expected as $name) {
            $permission = Permission::query()
                ->where('guard_name', 'web')
                ->where('name', $name)
                ->first();

            if ($permission === null) {
                $permission = new Permission;
                $permission->forceFill([
                    'name' => $name,
                    'guard_name' => 'web',
                    'is_code_owned' => true,
                ])->save();
            } elseif (! $permission->is_code_owned) {
                DB::table('permissions')
                    ->where('id', $permission->getKey())
                    ->update([
                        'is_code_owned' => true,
                        'updated_at' => now(),
                    ]);
            }
        }

        DB::table('permissions')
            ->where('guard_name', 'web')
            ->where('is_code_owned', true)
            ->whereNotIn('name', $expected)
            ->update([
                'is_code_owned' => false,
                'updated_at' => now(),
            ]);
        $this->permissions->synchronize();
    }

    private function synchronizeMappings(): void
    {
        $expectedMappingIds = [];

        foreach (self::IMMUTABLE_MAPPINGS as $groupName => $roleName) {
            $group = DirectoryGroup::query()
                ->where('name', $groupName)
                ->first();

            if ($group === null) {
                $group = DirectoryGroup::query()->create([
                    'name' => $groupName,
                    'external_id' => null,
                    'member_count' => null,
                    'status' => DirectoryGroup::STATUS_MISSING,
                    'first_seen_at' => null,
                    'last_seen_at' => null,
                    'last_synced_at' => null,
                ]);
            }

            $role = Role::query()
                ->where('guard_name', 'web')
                ->where('name', $roleName)
                ->firstOrFail();
            $mapping = DirectoryGroupRoleMapping::query()
                ->whereBelongsTo($group)
                ->whereBelongsTo($role)
                ->first();

            if ($mapping === null) {
                $mapping = new DirectoryGroupRoleMapping;
                $mapping->forceFill([
                    'directory_group_id' => $group->getKey(),
                    'role_id' => $role->getKey(),
                    'is_immutable' => true,
                ])->save();
            } elseif (! $mapping->is_immutable) {
                $mapping->forceFill([
                    'is_immutable' => true,
                ])->save();
            }

            $expectedMappingIds[] = $mapping->getKey();
        }

        DirectoryGroupRoleMapping::query()
            ->where('is_immutable', true)
            ->whereNotIn('id', $expectedMappingIds)
            ->update([
                'is_immutable' => false,
                'updated_at' => now(),
            ]);

        $mappings = DB::table('directory_group_role_mappings')
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
            ->get([
                'directory_groups.name as group_name',
                'roles.guard_name',
            ]);

        foreach ($mappings as $mapping) {
            if (! is_string($mapping->group_name)
                || $mapping->group_name === ''
                || $mapping->group_name
                    !== strtolower(trim($mapping->group_name))
                || preg_match('/[*?%]/', $mapping->group_name) === 1
                || in_array(
                    $mapping->group_name,
                    self::LEGACY_ALIASES,
                    true,
                )
                || $mapping->guard_name !== 'web') {
                throw new AuthorizationLifecycleException(
                    'mapping_integrity',
                );
            }
        }
    }
}
