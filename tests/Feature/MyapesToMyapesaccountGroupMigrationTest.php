<?php

namespace Tests\Feature;

use App\Models\DirectoryGroup;
use App\Models\Role;
use App\Models\RoleSource;
use App\Models\User;
use App\Services\AuthorizationProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MyapesToMyapesaccountGroupMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_merges_legacy_groups_when_canonical_names_already_exist(): void
    {
        $superAdminRole = Role::query()
            ->where('name', AuthorizationProfile::ROLE_SUPER_ADMIN)
            ->firstOrFail();

        $canonicalSuperAdmin = DirectoryGroup::query()
            ->where('name', 'myapesaccount.superadmin')
            ->firstOrFail();
        $legacySuperAdmin = DirectoryGroup::query()->create([
            'name' => 'myapes.superadmins',
            'status' => DirectoryGroup::STATUS_PRESENT,
            'app_enabled' => true,
        ]);
        $legacyStaff = DirectoryGroup::query()->create([
            'name' => 'myapes.staff',
            'status' => DirectoryGroup::STATUS_PRESENT,
            'app_enabled' => true,
        ]);

        DB::table('directory_group_role_mappings')->insert([
            'directory_group_id' => $legacySuperAdmin->id,
            'role_id' => $superAdminRole->id,
            'is_immutable' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_SUPER_ADMIN)
            ->create([
                'ldap_groups' => [
                    'myapes.superadmins',
                    'myapes.staff',
                ],
            ]);

        DB::table('role_sources')->insert([
            'user_id' => $user->id,
            'role_id' => $superAdminRole->id,
            'source' => RoleSource::SOURCE_DIRECTORY,
            'source_key' => 'directory:'.$legacySuperAdmin->id,
            'directory_group_id' => $legacySuperAdmin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('migrations')
            ->where(
                'migration',
                '2026_08_24_000002_migrate_myapes_to_myapesaccount_groups',
            )
            ->delete();

        $this->assertSame(
            0,
            Artisan::call('migrate', [
                '--path' => 'database/migrations/2026_08_24_000002_migrate_myapes_to_myapesaccount_groups.php',
                '--force' => true,
            ]),
        );

        $this->assertDatabaseMissing('directory_groups', [
            'name' => 'myapes.superadmins',
        ]);
        $this->assertDatabaseMissing('directory_groups', [
            'name' => 'myapes.staff',
        ]);
        $this->assertDatabaseHas('directory_groups', [
            'id' => $canonicalSuperAdmin->id,
            'name' => 'myapesaccount.superadmin',
        ]);
        $this->assertDatabaseHas('directory_groups', [
            'name' => 'myapesaccount.staff',
        ]);

        $this->assertDatabaseMissing('directory_group_role_mappings', [
            'directory_group_id' => $legacySuperAdmin->id,
        ]);
        $this->assertDatabaseHas('role_sources', [
            'user_id' => $user->id,
            'role_id' => $superAdminRole->id,
            'directory_group_id' => $canonicalSuperAdmin->id,
            'source_key' => 'directory:'.$canonicalSuperAdmin->id,
        ]);

        $user->refresh();
        $this->assertSame(
            ['myapesaccount.staff', 'myapesaccount.superadmin'],
            $user->ldap_groups,
        );

        $this->assertDatabaseMissing('directory_groups', [
            'id' => $legacySuperAdmin->id,
        ]);
        $this->assertDatabaseMissing('directory_groups', [
            'id' => $legacyStaff->id,
        ]);
    }
}
