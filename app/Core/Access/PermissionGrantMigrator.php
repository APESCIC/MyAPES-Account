<?php

namespace App\Core\Access;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Id-preserving permission rename helper (#292).
 *
 * Renames `permissions.name` in place so role_has_permissions,
 * model_has_permissions, and permission_sources keep their permission_id.
 * With an empty {@see PermissionNaming::aliases()} map this is a no-op.
 */
final class PermissionGrantMigrator
{
    public function __construct(
        private readonly PermissionGrantReporter $reporter,
    ) {}

    /**
     * @return array{
     *     renamed: int,
     *     before: array{
     *         permissions: int,
     *         role_grants: int,
     *         direct_grants: int,
     *         permission_sources: int,
     *         by_permission: array<string, array{roles: int, users: int, sources: int}>
     *     },
     *     after: array{
     *         permissions: int,
     *         role_grants: int,
     *         direct_grants: int,
     *         permission_sources: int,
     *         by_permission: array<string, array{roles: int, users: int, sources: int}>
     *     },
     *     grants_preserved: bool
     * }
     */
    public function migrateAliases(): array
    {
        $before = $this->reporter->snapshot();
        $renamed = 0;
        $aliases = PermissionNaming::aliases();

        if ($aliases !== [] && Schema::hasTable('permissions')) {
            $renamed = (int) DB::transaction(function () use ($aliases): int {
                $count = 0;

                foreach ($aliases as $from => $to) {
                    if ($from === $to) {
                        continue;
                    }

                    $updated = DB::table('permissions')
                        ->where('guard_name', 'web')
                        ->where('name', $from)
                        ->update([
                            'name' => $to,
                            'updated_at' => now(),
                        ]);

                    $count += $updated;
                }

                return $count;
            });

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $after = $this->reporter->snapshot();

        return [
            'renamed' => $renamed,
            'before' => $before,
            'after' => $after,
            'grants_preserved' => $this->reporter->grantsPreserved($before, $after),
        ];
    }
}
