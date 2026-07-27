<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        DB::table('users')->where('id', $user->id)->update(['role' => 'owner']);

        $exitCode = Artisan::call('myapes:access-compatibility-sync');
        $output = Artisan::output();

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('unsupported access levels', $output);
        $this->assertStringNotContainsString('private@example.com', $output);
        $this->assertStringNotContainsString('owner', $output);
    }

    public function test_command_is_a_successful_no_op_after_role_is_removed(): void
    {
        $user = User::factory()->accessLevel(User::ROLE_STAFF)->create();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        $this->artisan('myapes:access-compatibility-sync')
            ->expectsOutput('Legacy role column is absent; compatibility synchronization is not required.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame(User::ROLE_STAFF, $user->fresh()->accessLevel());
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
}
