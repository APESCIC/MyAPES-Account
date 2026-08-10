<?php

namespace Tests\Feature;

use App\Models\ShelterCase;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApesCicModuleFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_ticket_and_case_storage_exposes_the_additive_v013_contract(): void
    {
        $this->assertTrue(Schema::hasColumn('support_tickets', 'sub_core_key'));
        $this->assertTrue(Schema::hasColumns('shelter_cases', [
            'sub_core_key',
            'category',
            'priority',
            'opened_at',
            'resolved_at',
        ]));
        $this->assertTrue(Schema::hasColumns('case_updates', [
            'id',
            'shelter_case_id',
            'user_id',
            'body',
            'visibility',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_existing_model_creation_uses_compatible_sub_core_defaults(): void
    {
        $user = User::factory()->create();

        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'service_area' => 'operations',
            'subject' => 'Existing ticket path',
            'priority' => 'medium',
            'status' => 'open',
            'description' => 'Preserve the v0.12.1 creation contract.',
        ])->fresh();

        $case = ShelterCase::create([
            'user_id' => $user->id,
            'category' => 'membership',
            'priority' => 'high',
            'status' => 'waiting_on_user',
            'title' => 'APES CIC membership case',
            'details' => 'No pet relationship is required.',
            'opened_at' => now(),
        ])->fresh();

        $this->assertSame('apes-cic', $ticket->sub_core_key);
        $this->assertSame('shelter-rescue', $case->sub_core_key);
        $this->assertNull($case->pet_profile_id);
    }

    public function test_case_updates_store_an_explicit_bounded_visibility(): void
    {
        $user = User::factory()->create();
        $caseId = DB::table('shelter_cases')->insertGetId([
            'sub_core_key' => 'apes-cic',
            'pet_profile_id' => null,
            'user_id' => $user->id,
            'assigned_to' => null,
            'case_type' => null,
            'category' => 'general',
            'priority' => 'low',
            'status' => 'open',
            'title' => 'General enquiry',
            'details' => null,
            'opened_at' => now(),
            'resolved_at' => null,
            'closed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('case_updates')->insert([
            'shelter_case_id' => $caseId,
            'user_id' => $user->id,
            'body' => 'A public progress update.',
            'visibility' => 'public',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('case_updates', [
            'shelter_case_id' => $caseId,
            'visibility' => 'public',
        ]);
    }
}
