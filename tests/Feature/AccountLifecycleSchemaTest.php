<?php

namespace Tests\Feature;

use App\Models\ContactConsentEvent;
use App\Models\OidcLinkIntent;
use App\Models\User;
use App\Models\UserContactPreference;
use App\Models\UserServiceSelection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class AccountLifecycleSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_lifecycle_schema_and_relations_are_available(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(Schema::hasColumns('users', [
            'username',
            'onboarding_completed_at',
        ]));
        $this->assertTrue(Schema::hasColumns('user_profiles', [
            'address_line_1',
            'address_line_2',
            'town_city',
            'county',
            'postcode',
            'country',
            'mobile_number',
            'landline_number',
            'whatsapp_number',
            'telegram_username',
        ]));

        $selection = $user->serviceSelections()->firstOrCreate([
            'sub_core_key' => 'apes-cic',
        ]);
        $preference = $user->contactPreference()->firstOrCreate([]);
        $event = $user->contactConsentEvents()->create([
            'channel' => 'email',
            'granted' => true,
            'policy_version' => '2026-08',
            'source' => 'preferences',
            'actor_user_id' => $user->id,
            'recorded_at' => now(),
        ]);
        $intent = $user->oidcLinkIntents()->create([
            'token_hash' => hash('sha256', 'token'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->assertInstanceOf(UserServiceSelection::class, $selection);
        $this->assertInstanceOf(UserContactPreference::class, $preference);
        $this->assertInstanceOf(ContactConsentEvent::class, $event);
        $this->assertInstanceOf(OidcLinkIntent::class, $intent);
        $this->assertFalse($preference->calls);
        $this->assertFalse($preference->sms);
        $this->assertFalse($preference->whatsapp);
        $this->assertFalse($preference->telegram);
        $this->assertFalse($preference->email);
    }

    public function test_upgrade_backfills_public_accounts_without_inventing_consent(): void
    {
        $public = User::factory()->create([
            'email' => 'PUBLIC@EXAMPLE.COM',
            'identity_type' => User::IDENTITY_LOCAL,
        ]);
        $staff = User::factory()->cloudronIdentity('staff-subject')->create([
            'email' => 'STAFF@EXAMPLE.COM',
        ]);
        $migration = require database_path(
            'migrations/2026_08_08_000000_add_account_lifecycle_foundation.php',
        );

        $migration->down();
        $migration->up();

        $this->assertSame('public@example.com', DB::table('users')->where('id', $public->id)->value('email'));
        $this->assertSame('staff@example.com', DB::table('users')->where('id', $staff->id)->value('email'));
        $this->assertSame(
            ['apes-cic', 'pet-care-clinic', 'shelter-rescue'],
            DB::table('user_service_selections')
                ->where('user_id', $public->id)
                ->orderBy('sub_core_key')
                ->pluck('sub_core_key')
                ->all(),
        );
        $this->assertDatabaseMissing('user_service_selections', ['user_id' => $staff->id]);
        $this->assertDatabaseHas('user_contact_preferences', [
            'user_id' => $public->id,
            'calls' => false,
            'sms' => false,
            'whatsapp' => false,
            'telegram' => false,
            'email' => false,
        ]);
        $this->assertDatabaseCount('contact_consent_events', 0);
    }

    public function test_upgrade_refuses_case_insensitive_email_collisions(): void
    {
        User::factory()->create(['email' => 'collision@example.com']);
        $second = User::factory()->create(['email' => 'other@example.com']);
        DB::table('users')->where('id', $second->id)->update(['email' => 'COLLISION@example.com']);
        $migration = require database_path(
            'migrations/2026_08_08_000000_add_account_lifecycle_foundation.php',
        );

        $migration->down();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('case-insensitive email collisions');

        $migration->up();
    }

    public function test_consent_events_are_append_only(): void
    {
        $user = User::factory()->create();
        $event = $user->contactConsentEvents()->create([
            'channel' => 'email',
            'granted' => true,
            'policy_version' => '2026-08',
            'source' => 'preferences',
            'actor_user_id' => $user->id,
            'recorded_at' => now(),
        ]);

        try {
            $event->update(['granted' => false]);
            $this->fail('A consent event was updated.');
        } catch (\LogicException) {
            $this->assertTrue(true);
        }

        $this->expectException(\LogicException::class);
        $event->delete();
    }

    public function test_account_identifiers_are_normalized_and_whatsapp_falls_back_to_mobile(): void
    {
        $user = User::factory()->create([
            'username' => 'Mixed.Case',
            'email' => 'Mixed.Case@Example.COM',
        ]);
        $profile = $user->profile()->create([
            'mobile_number' => '+447400123456',
        ]);

        $this->assertSame('mixed.case', $user->username);
        $this->assertSame('mixed.case@example.com', $user->email);
        $this->assertSame('+447400123456', $profile->effectiveWhatsappNumber());

        $profile->update(['whatsapp_number' => '+447400654321']);
        $this->assertSame('+447400654321', $profile->effectiveWhatsappNumber());
    }
}
