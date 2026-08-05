<?php

namespace Tests\Feature;

use App\Support\AccessCompatibilityDatabaseGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class AccessCompatibilityMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runInMaintenanceMode(
            fn () => $this->phaseBMigration()->down(),
        );
    }

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
            $this->legacyUser(3, 'admin@example.com', 'admin', null),
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

    public function test_legacy_writes_remain_synchronized_after_the_migration(): void
    {
        DB::table('users')->insert(
            $this->legacyUser(1, 'legacy@example.com', 'staff', 'cloudron-subject'),
        );

        $this->assertDatabaseHas('users', [
            'id' => 1,
            'identity_type' => 'cloudron_oidc',
            'legacy_access_level' => 'staff',
            'role' => 'staff',
        ]);

        DB::table('users')->where('id', 1)->update([
            'oidc_sub' => '   ',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => 1,
            'identity_type' => 'local',
            'legacy_access_level' => 'admin',
            'role' => 'admin',
        ]);
    }

    public function test_database_guard_rejects_unsupported_legacy_inserts(): void
    {
        $this->expectException(QueryException::class);

        DB::table('users')->insert(
            $this->legacyUser(1, 'invalid@example.com', 'owner', null),
        );
    }

    public function test_database_guard_rejects_same_name_tampered_definitions_and_reinstalls_canonical_triggers(): void
    {
        $guard = app(AccessCompatibilityDatabaseGuard::class);
        $guard->drop();
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::unprepared(
                'CREATE TRIGGER users_access_compatibility_insert
                 AFTER INSERT ON users BEGIN SELECT 1; END',
            );
            DB::unprepared(
                'CREATE TRIGGER users_access_compatibility_update
                 AFTER UPDATE ON users BEGIN SELECT 1; END',
            );
        } else {
            DB::unprepared(
                'CREATE TRIGGER users_access_compatibility_insert
                 BEFORE INSERT ON users FOR EACH ROW SET @myapes_guard = 1',
            );
            DB::unprepared(
                'CREATE TRIGGER users_access_compatibility_update
                 BEFORE UPDATE ON users FOR EACH ROW SET @myapes_guard = 1',
            );
        }

        $this->assertFalse($guard->isInstalled());

        $guard->install();

        $this->assertTrue($guard->isInstalled());
        DB::table('users')->insert(
            $this->legacyUser(10, 'canonical@example.com', 'staff', null),
        );
        $this->assertDatabaseHas('users', [
            'id' => 10,
            'legacy_access_level' => 'staff',
            'identity_type' => 'local',
        ]);
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

    public function test_guard_installation_failure_removes_partially_added_columns(): void
    {
        $migration = $this->migration();
        $migration->down();

        $this->app->bind(
            AccessCompatibilityDatabaseGuard::class,
            static fn (): AccessCompatibilityDatabaseGuard => new class extends AccessCompatibilityDatabaseGuard
            {
                public function install(): void
                {
                    throw new RuntimeException('Simulated database guard installation failure.');
                }
            },
        );

        try {
            $migration->up();
            $this->fail('The migration accepted a failed database guard installation.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated database guard installation failure.',
                $exception->getMessage(),
            );
        }

        $this->assertFalse(Schema::hasColumn('users', 'identity_type'));
        $this->assertFalse(Schema::hasColumn('users', 'legacy_access_level'));
        $this->assertTrue(Schema::hasColumn('users', 'role'));
    }

    #[DataProvider('invalidLegacyRoleProvider')]
    public function test_upgrade_rejects_null_or_blank_roles_before_adding_columns(
        ?string $role,
    ): void {
        $migration = $this->migration();
        $migration->down();

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable()->change();
        });

        DB::table('users')->insert(
            $this->legacyUser(1, 'invalid@example.com', $role, null),
        );

        try {
            $migration->up();
            $this->fail('The migration accepted a null or blank legacy role.');
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

    /**
     * @return array<string, array{?string}>
     */
    public static function invalidLegacyRoleProvider(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'whitespace' => ['   '],
        ];
    }

    private function migration(): Migration
    {
        /** @var Migration $migration */
        $migration = require database_path(
            'migrations/2026_07_27_130000_add_access_compatibility_to_users_table.php',
        );

        return $migration;
    }

    private function phaseBMigration(): Migration
    {
        /** @var Migration $migration */
        $migration = require database_path(
            'migrations/2026_07_28_000100_cut_over_authorization_domain.php',
        );

        return $migration;
    }

    /**
     * @return array<string, int|string|null>
     */
    private function legacyUser(int $id, string $email, ?string $role, ?string $oidcSub): array
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
