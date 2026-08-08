<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountLifecycleReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'myapes.contact_consent.policy_version' => '2026-08',
            'myapes.contact_consent.privacy_notice_url' => 'https://apes.example/privacy',
        ]);
    }

    public function test_preflight_and_post_migration_checks_accept_a_ready_installation(): void
    {
        User::factory()->create();

        $this->artisan('myapes:accounts:preflight')
            ->expectsOutputToContain('Account lifecycle preflight: ok')
            ->assertSuccessful();
        $this->artisan('myapes:accounts:check')
            ->expectsOutputToContain('Account lifecycle readiness: ok')
            ->assertSuccessful();
    }

    public function test_preflight_rejects_email_collisions_and_missing_legal_configuration(): void
    {
        $first = User::factory()->create(['email' => 'collision@example.com']);
        $second = User::factory()->create();
        DB::table('users')->where('id', $second->id)->update([
            'email' => strtoupper($first->email),
        ]);

        $this->artisan('myapes:accounts:preflight')
            ->expectsOutputToContain('email_collision')
            ->assertFailed();

        DB::table('users')->where('id', $second->id)->delete();
        config(['myapes.contact_consent.privacy_notice_url' => null]);

        $this->artisan('myapes:accounts:preflight')
            ->expectsOutputToContain('privacy_notice_url')
            ->assertFailed();
    }

    public function test_post_migration_check_rejects_missing_public_account_defaults(): void
    {
        $user = User::factory()->create();
        $user->contactPreference()->delete();

        $this->artisan('myapes:accounts:check')
            ->expectsOutputToContain('contact_preference_backfill')
            ->assertFailed();
    }
}
