<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class AuthorizationPermissionSynchronizer
{
    public function __construct(
        private readonly AuthorizationProfile $profile,
    ) {}

    public function synchronize(): void
    {
        DB::transaction(function (): void {
            $roleIds = DB::table('roles')
                ->where('guard_name', 'web')
                ->whereIn('name', $this->profile->protectedRolesByPrecedence())
                ->pluck('id', 'name');
            $permissionIds = DB::table('permissions')
                ->where('guard_name', 'web')
                ->whereIn('name', $this->profile->permissions())
                ->pluck('id', 'name');

            if ($roleIds->count() !== count($this->profile->protectedRolesByPrecedence())
                || $permissionIds->count() !== count($this->profile->permissions())) {
                throw new RuntimeException(
                    'Protected authorization metadata is incomplete.',
                );
            }

            DB::table('role_has_permissions')
                ->whereIn('role_id', $roleIds->values())
                ->delete();

            $rows = [];

            foreach ($this->profile->permissionMatrix() as $roleName => $permissions) {
                foreach ($permissions as $permission) {
                    $rows[] = [
                        'role_id' => $roleIds->get($roleName),
                        'permission_id' => $permissionIds->get($permission),
                    ];
                }
            }

            if ($rows !== []) {
                DB::table('role_has_permissions')->insert($rows);
            }

            $store = config('permission.cache.store');
            $cache = $store === 'default'
                ? app('cache')->store()
                : app('cache')->store($store);
            $cache->forget(config('permission.cache.key'));
        });
    }
}
