<?php

namespace Tests\Feature;

use App\Models\PetCareConsultation;
use App\Models\PetProfile;
use App\Models\ShelterCase;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAttentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_user_sees_only_their_six_most_recent_open_items(): void
    {
        $serviceUser = User::factory()->accessLevel(User::ROLE_SERVICE_USER)->create();
        $otherUser = User::factory()->accessLevel(User::ROLE_SERVICE_USER)->create();
        $pet = $this->petFor($serviceUser);

        $expectedTitles = [];
        for ($index = 0; $index < 7; $index++) {
            $ticket = $this->ticketFor($serviceUser, "Own open ticket {$index}");
            $this->setUpdatedAt($ticket, now()->subMinutes($index + 1));
            $expectedTitles[] = $ticket->subject;
        }

        $closedTicket = $this->ticketFor($serviceUser, 'Closed ticket', 'closed');
        $closedTicket->update(['closed_at' => now()]);
        $this->setUpdatedAt($closedTicket, now());

        $otherTicket = $this->ticketFor($otherUser, 'Another user ticket');
        $this->setUpdatedAt($otherTicket, now());

        ShelterCase::create([
            'pet_profile_id' => $pet->id,
            'user_id' => $serviceUser->id,
            'case_type' => 'rescue',
            'status' => 'closed',
            'title' => 'Closed shelter case',
            'details' => 'Complete',
            'closed_at' => now(),
        ]);

        $response = $this->actingAs($serviceUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('ticketCount', 8);
        $response->assertViewHas('attentionItems', function (array $items) use ($expectedTitles): bool {
            return count($items) === 6
                && array_column($items, 'title') === array_slice($expectedTitles, 0, 6);
        });
        $response->assertDontSee('Closed ticket');
        $response->assertDontSee('Another user ticket');
    }

    public function test_staff_attention_feed_includes_open_items_across_users_and_services(): void
    {
        $staff = User::factory()->accessLevel(User::ROLE_STAFF)->create();
        $firstUser = User::factory()->accessLevel(User::ROLE_SERVICE_USER)->create();
        $secondUser = User::factory()->accessLevel(User::ROLE_SERVICE_USER)->create();
        $firstPet = $this->petFor($firstUser);
        $secondPet = $this->petFor($secondUser);

        $ticket = $this->ticketFor($firstUser, 'Cross-service ticket');
        $case = ShelterCase::create([
            'pet_profile_id' => $firstPet->id,
            'user_id' => $firstUser->id,
            'case_type' => 'rescue',
            'status' => 'in_review',
            'title' => 'Cross-service shelter case',
            'details' => 'Review needed',
        ]);
        $consultation = PetCareConsultation::create([
            'pet_profile_id' => $secondPet->id,
            'user_id' => $secondUser->id,
            'subject' => 'Cross-service consultation',
            'status' => 'open',
            'notes' => 'Review needed',
        ]);

        $this->setUpdatedAt($ticket, now()->subMinutes(3));
        $this->setUpdatedAt($case, now()->subMinutes(2));
        $this->setUpdatedAt($consultation, now()->subMinute());

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('attentionItems', function (array $items): bool {
            return array_column($items, 'title') === [
                'Cross-service consultation',
                'Cross-service shelter case',
                'Cross-service ticket',
            ];
        });
    }

    public function test_admin_attention_feed_includes_normalized_open_items_for_all_users(): void
    {
        $admin = User::factory()->accessLevel(User::ROLE_ADMIN)->create();
        $serviceUser = User::factory()->accessLevel(User::ROLE_SERVICE_USER)->create();
        $ticket = $this->ticketFor($serviceUser, 'Admin-visible ticket');
        $this->setUpdatedAt($ticket, now()->subMinute());

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('attentionItems', function (array $items): bool {
            if (count($items) !== 1 || $items[0]['title'] !== 'Admin-visible ticket') {
                return false;
            }

            foreach (['type', 'title', 'status', 'updatedAt', 'url'] as $key) {
                if (! array_key_exists($key, $items[0])) {
                    return false;
                }
            }

            return true;
        });
    }

    private function ticketFor(User $user, string $subject, string $status = 'open'): SupportTicket
    {
        return SupportTicket::create([
            'user_id' => $user->id,
            'service_area' => 'it',
            'subject' => $subject,
            'priority' => 'high',
            'status' => $status,
            'description' => 'Dashboard attention test',
        ]);
    }

    private function petFor(User $user): PetProfile
    {
        return PetProfile::create([
            'user_id' => $user->id,
            'service_domain' => PetProfile::DOMAIN_PETCARE,
            'name' => 'QA Pet '.$user->id,
            'species' => 'Bearded dragon',
            'sex' => 'unknown',
            'neutering_status' => 'unknown',
        ]);
    }

    private function setUpdatedAt(object $model, mixed $updatedAt): void
    {
        $model->timestamps = false;
        $model->forceFill(['updated_at' => $updatedAt])->saveQuietly();
        $model->timestamps = true;
    }
}
