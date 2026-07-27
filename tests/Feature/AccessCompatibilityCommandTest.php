<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AccessCompatibilityDatabaseGuard;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessCompatibilityCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_reconciles_compatibility_fields_from_legacy_sources(): void
    {
        $this->assertArrayHasKey(
            'myapes:access-compatibility-sync',
            Artisan::all(),
            'The access compatibility synchronization command is missing.',
        );

        $user = User::factory()->create([
            'email' => 'directory@example.com',
            'oidc_sub' => 'directory-subject',
        ]);
        DB::table('users')->where('id', $user->id)->update([
            'identity_type' => User::IDENTITY_LOCAL,
            'legacy_access_level' => User::ROLE_STAFF,
            'role' => User::ROLE_ADMIN,
        ]);

        $this->artisan('myapes:access-compatibility-sync')
            ->expectsOutput('Access compatibility fields synchronized.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'identity_type' => User::IDENTITY_CLOUDRON_OIDC,
            'legacy_access_level' => User::ROLE_ADMIN,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_command_rejects_invalid_roles_without_disclosing_user_data(): void
    {
        $user = User::factory()->create(['email' => 'private@example.com']);
        app(AccessCompatibilityDatabaseGuard::class)->drop();
        DB::table('users')->where('id', $user->id)->update(['role' => 'owner']);

        $exitCode = Artisan::call('myapes:access-compatibility-sync');
        $output = Artisan::output();

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('unsupported access levels', $output);
        $this->assertStringNotContainsString('private@example.com', $output);
        $this->assertStringNotContainsString('owner', $output);
    }

    #[DataProvider('nullOrBlankLegacyRoleProvider')]
    public function test_command_rejects_null_or_blank_roles_without_disclosing_user_data(
        ?string $role,
    ): void {
        $user = User::factory()->create(['email' => 'private@example.com']);
        app(AccessCompatibilityDatabaseGuard::class)->drop();

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->nullable()->change();
        });

        DB::table('users')->where('id', $user->id)->update(['role' => $role]);

        $exitCode = Artisan::call('myapes:access-compatibility-sync');
        $output = Artisan::output();

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('unsupported access levels', $output);
        $this->assertStringNotContainsString('private@example.com', $output);
    }

    public function test_command_is_a_successful_no_op_after_role_is_removed(): void
    {
        $user = User::factory()->accessLevel(User::ROLE_STAFF)->create();

        app(AccessCompatibilityDatabaseGuard::class)->drop();
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        $this->artisan('myapes:access-compatibility-sync')
            ->expectsOutput('Legacy role column is absent; compatibility synchronization is not required.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame(User::ROLE_STAFF, $user->fresh()->accessLevel());
    }

    public function test_command_fails_when_the_database_guard_is_missing(): void
    {
        User::factory()->create();
        app(AccessCompatibilityDatabaseGuard::class)->drop();

        $this->artisan('myapes:access-compatibility-sync')
            ->expectsOutput('Access compatibility database guard is missing.')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_command_fails_when_reconciliation_does_not_reach_its_postcondition(): void
    {
        User::factory()->accessLevel(User::ROLE_ADMIN)->create();
        $this->installForcedDivergenceTrigger();

        $this->artisan('myapes:access-compatibility-sync')
            ->expectsOutput('Access compatibility synchronization postcondition failed.')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_command_fails_when_compatibility_fields_are_missing(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['identity_type']);
            $table->dropIndex(['legacy_access_level']);
            $table->dropColumn(['identity_type', 'legacy_access_level']);
        });

        $this->artisan('myapes:access-compatibility-sync')
            ->expectsOutput('Access compatibility fields are missing.')
            ->assertExitCode(Command::FAILURE);
    }

    /**
     * @return array<string, array{?string}>
     */
    public static function nullOrBlankLegacyRoleProvider(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'whitespace' => ['   '],
        ];
    }

    private function installForcedDivergenceTrigger(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::unprepared(
                "CREATE TRIGGER force_access_divergence
                 AFTER UPDATE OF legacy_access_level ON users
                 BEGIN
                     UPDATE users SET legacy_access_level = 'staff' WHERE id = NEW.id;
                 END",
            );

            return;
        }

        DB::unprepared(
            "CREATE TRIGGER force_access_divergence
             BEFORE UPDATE ON users
             FOR EACH ROW FOLLOWS users_access_compatibility_update
             SET NEW.legacy_access_level = 'staff'",
        );
    }
}
