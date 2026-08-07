<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\PermissionSource;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthorizationDirectPermissionMaterializer;
use App\Services\AuthorizationProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModulePermissionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rollback_removes_only_exact_code_owned_web_module_permissions(): void
    {
        $now = now();
        $user = User::factory()->create();
        $role = Role::query()
            ->where('guard_name', 'web')
            ->where('name', AuthorizationProfile::ROLE_STAFF)
            ->firstOrFail();
        $demotedName = 'apes-cic.tickets.comment-own';
        $demotedId = (int) DB::table('permissions')
            ->where('guard_name', 'web')
            ->where('name', $demotedName)
            ->value('id');
        $demotedPermission = Permission::query()->findOrFail($demotedId);
        app(AuthorizationDirectPermissionMaterializer::class)->grant(
            $user,
            $demotedPermission,
            PermissionSource::SOURCE_SYSTEM,
        );
        DB::table('permissions')->where('id', $demotedId)->update([
            'is_code_owned' => false,
        ]);
        $customId = (int) DB::table('permissions')->insertGetId([
            'name' => 'apes-cic.custom.export',
            'guard_name' => 'web',
            'is_code_owned' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $otherGuardId = (int) DB::table('permissions')->insertGetId([
            'name' => 'apes-cic.tickets.update-all',
            'guard_name' => 'api',
            'is_code_owned' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $preservedIds = [$demotedId, $customId, $otherGuardId];

        foreach ($preservedIds as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $role->id,
            ]);
        }
        $migration = $this->moduleMigration();

        try {
            $migration->down();

            $this->assertFalse(Schema::hasTable('module_installations'));
            $this->assertDatabaseMissing('permissions', [
                'name' => 'apes-cic.tickets.update-all',
                'guard_name' => 'web',
                'is_code_owned' => true,
            ]);
            $this->assertDatabaseHas('permissions', [
                'id' => $demotedId,
                'name' => $demotedName,
                'guard_name' => 'web',
                'is_code_owned' => false,
            ]);
            $this->assertDatabaseHas('permissions', [
                'id' => $customId,
                'name' => 'apes-cic.custom.export',
                'guard_name' => 'web',
                'is_code_owned' => false,
            ]);
            $this->assertDatabaseHas('permissions', [
                'id' => $otherGuardId,
                'name' => 'apes-cic.tickets.update-all',
                'guard_name' => 'api',
                'is_code_owned' => true,
            ]);
            $this->assertDatabaseHas('permission_sources', [
                'user_id' => $user->id,
                'permission_id' => $demotedId,
                'source' => PermissionSource::SOURCE_SYSTEM,
            ]);
            $this->assertDatabaseHas('model_has_permissions', [
                'model_id' => $user->id,
                'permission_id' => $demotedId,
            ]);

            foreach ($preservedIds as $permissionId) {
                $this->assertDatabaseHas('role_has_permissions', [
                    'permission_id' => $permissionId,
                    'role_id' => $role->id,
                ]);
            }
        } finally {
            app(AuthorizationDirectPermissionMaterializer::class)->revoke(
                $user,
                $demotedPermission,
                PermissionSource::SOURCE_SYSTEM,
            );
            DB::table('role_has_permissions')
                ->whereIn('permission_id', $preservedIds)
                ->delete();
            DB::table('permissions')->whereIn('id', $preservedIds)->delete();
            $migration->up();
        }
    }

    private function moduleMigration(): Migration
    {
        /** @var Migration $migration */
        $migration = require database_path(
            'migrations/2026_08_06_000000_create_module_installations_table.php',
        );

        return $migration;
    }
}
