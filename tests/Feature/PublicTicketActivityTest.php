<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Services\AuthorizationProfile;
use App\Services\ModuleInstallationSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PublicTicketActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ModuleInstallationSynchronizer::class)->synchronize();
    }

    public function test_public_owner_sees_description_in_activity_on_a_new_ticket(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $description = 'The garden gate latch is jammed shut.';

        $this->actingAs($owner)
            ->post(route('apes-cic.tickets.store'), [
                'service_area' => 'operations_facilities',
                'sub_category' => 'premises',
                'subject' => 'Garden gate latch',
                'priority' => 'medium',
                'description' => $description,
            ])
            ->assertRedirect();

        $ticket = SupportTicket::query()
            ->where('subject', 'Garden gate latch')
            ->firstOrFail();

        $this->actingAs($owner)
            ->get(route('apes-cic.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('data-ticket-activity', false)
            ->assertSee('data-ticket-activity-opener', false)
            ->assertSeeInOrder([
                'Activity',
                'You can add an update (comment) to this ticket.',
                $description,
            ])
            ->assertDontSee('Ticket created.')
            ->assertDontSee('for="status"', false)
            ->assertDontSee('Delete ticket')
            ->assertDontSee('Withdraw');
    }

    public function test_public_owner_sees_description_in_activity_when_the_ticket_has_no_messages(): void
    {
        $owner = User::factory()->create();
        $description = 'Need a copy of my volunteer rota.';
        $ticket = SupportTicket::query()->create([
            'sub_core_key' => 'apes-cic',
            'user_id' => $owner->id,
            'service_area' => 'operations_facilities',
            'sub_category' => 'premises',
            'subject' => 'Volunteer rota',
            'priority' => 'low',
            'status' => 'open',
            'description' => $description,
        ]);

        $this->assertSame(0, $ticket->messages()->count());

        $this->actingAs($owner)
            ->get(route('apes-cic.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('data-ticket-activity-opener', false)
            ->assertSeeInOrder([
                'Activity',
                $description,
            ]);
    }

    public function test_public_owner_is_told_they_can_add_an_update(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->ticketFor($owner, 'Owner update hint');

        $this->actingAs($owner)
            ->get(route('apes-cic.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('data-ticket-activity-hint', false)
            ->assertSeeText('You can add an update (comment) to this ticket.')
            ->assertSee('Add message')
            ->assertDontSee('Save ticket');
    }

    public function test_public_owner_still_sees_description_after_a_staff_public_reply(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create();
        $description = 'Water bowl is leaking in the outdoor run.';
        $ticket = $this->ticketFor($owner, 'Outdoor run leak', $description);

        $this->actingAs($staff)->put(route('apes-cic.tickets.update', $ticket), [
            'message' => 'We will inspect the run this afternoon.',
            'visibility' => 'public',
        ])->assertRedirect(route('apes-cic.tickets.show', $ticket));

        $this->actingAs($owner)
            ->get(route('apes-cic.tickets.show', $ticket))
            ->assertOk()
            ->assertSeeInOrder([
                'Activity',
                $description,
                'We will inspect the run this afternoon.',
            ]);
    }

    public function test_public_owner_does_not_see_internal_staff_notes_in_activity(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create();
        $ticket = $this->ticketFor($owner, 'Internal note privacy');

        SupportTicketMessage::query()->create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $staff->id,
            'message' => 'Internal triage note stays private.',
            'is_staff_note' => true,
        ]);

        $this->actingAs($owner)
            ->get(route('apes-cic.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('data-ticket-activity-opener', false)
            ->assertDontSee('Internal triage note stays private.');
    }

    public function test_staff_see_description_in_activity_without_the_owner_update_hint(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create();
        $description = 'Staff should still see the opening description.';
        $ticket = $this->ticketFor($owner, 'Staff activity view', $description);

        $this->actingAs($staff)
            ->get(route('apes-cic.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('data-ticket-activity-opener', false)
            ->assertSeeInOrder([
                'Activity',
                $description,
            ])
            ->assertDontSee('data-ticket-activity-hint', false)
            ->assertDontSee('You can add an update (comment) to this ticket.');
    }

    private function ticketFor(User $owner, string $subject, string $description = 'Public ticket description.'): SupportTicket
    {
        return SupportTicket::query()->create([
            'sub_core_key' => 'apes-cic',
            'user_id' => $owner->id,
            'service_area' => 'operations_facilities',
            'sub_category' => 'premises',
            'subject' => $subject,
            'priority' => 'medium',
            'status' => 'open',
            'description' => $description,
        ]);
    }
}
