<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class AccessCompatibilityMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_install_adds_access_compatibility_columns_without_removing_role(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'identity_type',
            'legacy_access_level',
            'role',
        ]));
    }

    public function test_upgrade_backfills_every_access_level_and_identity_type(): void
    {
        $migration = $this->migration();
        $migration->down();

        DB::table('users')->insert([
            $this->legacyUser(1, 'service@example.com', 'service_user', null),
            $this->legacyUser(2, 'staff@example.com', 'staff', 'cloudron-staff'),
            $this->legacyUser(3, 'admin@example.com', 'admin', ''),
            $this->legacyUser(4, 'superadmin@example.com', 'superadmin', '   '),
        ]);

        $migration->up();

        $this->assertSame([
            ['identity_type' => 'local', 'legacy_access_level' => 'service_user', 'role' => 'service_user'],
            ['identity_type' => 'cloudron_oidc', 'legacy_access_level' => 'staff', 'role' => 'staff'],
            ['identity_type' => 'local', 'legacy_access_level' => 'admin', 'role' => 'admin'],
            ['identity_type' => 'local', 'legacy_access_level' => 'superadmin', 'role' => 'superadmin'],
        ], DB::table('users')
            ->orderBy('id')
            ->get(['identity_type', 'legacy_access_level', 'role'])
            ->map(static fn (object $user): array => (array) $user)
            ->all());
    }

    public function test_upgrade_rejects_unknown_roles_before_adding_columns(): void
    {
        $migration = $this->migration();
        $migration->down();

        DB::table('users')->insert(
            $this->legacyUser(1, 'invalid@example.com', 'owner', null),
        );

        try {
            $migration->up();
            $this->fail('The migration accepted an unsupported legacy role.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Cannot add access compatibility fields while users contain unsupported roles.',
                $exception->getMessage(),
            );
        }

        $this->assertFalse(Schema::hasColumn('users', 'identity_type'));
        $this->assertFalse(Schema::hasColumn('users', 'legacy_access_level'));
        $this->assertTrue(Schema::hasColumn('users', 'role'));
    }

    private function migration(): Migration
    {
        /** @var Migration $migration */
        $migration = require database_path(
            'migrations/2026_07_27_130000_add_access_compatibility_to_users_table.php',
        );

        return $migration;
    }

    /**
     * @return array<string, int|string|null>
     */
    private function legacyUser(int $id, string $email, string $role, ?string $oidcSub): array
    {
        return [
            'id' => $id,
            'oidc_sub' => $oidcSub,
            'name' => "Legacy user {$id}",
            'email' => $email,
            'role' => $role,
            'password' => null,
            'created_at' => '2026-07-27 12:00:00',
            'updated_at' => '2026-07-27 12:00:00',
        ];
    }
}
