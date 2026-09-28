<?php

namespace App\Core\Access;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pre/post grant counts for permission renames (#292).
 *
 * With an empty alias map this is a no-op report: counts stay identical before
 * and after AuthorizationMetadataSynchronizer runs.
 */
final class PermissionGrantReporter
{
    /**
     * @return array{
     *     permissions: int,
     *     role_grants: int,
     *     direct_grants: int,
     *     permission_sources: int,
     *     by_permission: array<string, array{roles: int, users: int, sources: int}>
     * }
     */
    public function snapshot(): array
    {
        if (! Schema::hasTable('permissions')) {
            return [
                'permissions' => 0,
                'role_grants' => 0,
                'direct_grants' => 0,
                'permission_sources' => 0,
                'by_permission' => [],
            ];
        }

        $permissions = DB::table('permissions')
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name']);

        $roleGrants = Schema::hasTable('role_has_permissions')
            ? (int) DB::table('role_has_permissions')->count()
            : 0;
        $directGrants = Schema::hasTable('model_has_permissions')
            ? (int) DB::table('model_has_permissions')->count()
            : 0;
        $sources = Schema::hasTable('permission_sources')
            ? (int) DB::table('permission_sources')->count()
            : 0;

        $byPermission = [];

        foreach ($permissions as $permission) {
            $roleCount = Schema::hasTable('role_has_permissions')
                ? (int) DB::table('role_has_permissions')
                    ->where('permission_id', $permission->id)
                    ->count()
                : 0;
            $userCount = Schema::hasTable('model_has_permissions')
                ? (int) DB::table('model_has_permissions')
                    ->where('permission_id', $permission->id)
                    ->count()
                : 0;
            $sourceCount = Schema::hasTable('permission_sources')
                ? (int) DB::table('permission_sources')
                    ->where('permission_id', $permission->id)
                    ->count()
                : 0;

            $byPermission[$permission->name] = [
                'roles' => $roleCount,
                'users' => $userCount,
                'sources' => $sourceCount,
            ];
        }

        return [
            'permissions' => $permissions->count(),
            'role_grants' => $roleGrants,
            'direct_grants' => $directGrants,
            'permission_sources' => $sources,
            'by_permission' => $byPermission,
        ];
    }

    /**
     * @param  array{
     *     permissions: int,
     *     role_grants: int,
     *     direct_grants: int,
     *     permission_sources: int,
     *     by_permission: array<string, array{roles: int, users: int, sources: int}>
     * }  $before
     * @param  array{
     *     permissions: int,
     *     role_grants: int,
     *     direct_grants: int,
     *     permission_sources: int,
     *     by_permission: array<string, array{roles: int, users: int, sources: int}>
     * }  $after
     */
    public function grantsPreserved(array $before, array $after): bool
    {
        return $before['role_grants'] === $after['role_grants']
            && $before['direct_grants'] === $after['direct_grants']
            && $before['permission_sources'] === $after['permission_sources']
            && $before['by_permission'] === $after['by_permission'];
    }
}
