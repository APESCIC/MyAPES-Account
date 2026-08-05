<?php

namespace Tests\Feature;

use App\Models\PetCareConsultation;
use App\Models\PetProfile;
use App\Models\ShelterCase;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_owners_keep_public_access_while_other_public_users_are_denied(): void
    {
        $owner = $this->user(User::ROLE_SERVICE_USER);
        $other = $this->user(User::ROLE_SERVICE_USER);
        [$ticket, $pet, $case, $consultation] = $this->resourcesFor($owner);

        $this->actingAsWithQaContext($owner);
        foreach ([$ticket, $pet, $case, $consultation] as $resource) {
            $this->assertTrue(Gate::allows('view', $resource));
            $this->assertTrue(Gate::allows('update', $resource));
        }
        $this->assertFalse(Gate::allows('delete', $ticket));

        $this->actingAsWithQaContext($other);
        foreach ([$ticket, $pet, $case, $consultation] as $resource) {
            $this->assertFalse(Gate::allows('view', $resource));
            $this->assertFalse(Gate::allows('update', $resource));
        }
    }

    public function test_current_staff_context_can_access_all_resources_and_staff_actions(): void
    {
        $owner = $this->user(User::ROLE_SERVICE_USER);
        $staff = $this->user(User::ROLE_STAFF);
        [$ticket, $pet, $case, $consultation] = $this->resourcesFor($owner);
        $this->actingAsWithQaContext($staff);

        foreach ([$ticket, $pet, $case, $consultation] as $resource) {
            $this->assertTrue(Gate::allows('view', $resource));
            $this->assertTrue(Gate::allows('update', $resource));
        }
        $this->assertTrue(Gate::allows('delete', $ticket));
    }

    /**
     * @return array{SupportTicket, PetProfile, ShelterCase, PetCareConsultation}
     */
    private function resourcesFor(User $owner): array
    {
        $ticket = SupportTicket::query()->create([
            'user_id' => $owner->id,
            'service_area' => 'it',
            'subject' => 'Policy ticket',
            'description' => 'Policy coverage',
        ]);
        $pet = PetProfile::query()->create([
            'user_id' => $owner->id,
            'service_domain' => PetProfile::DOMAIN_SHELTER,
            'name' => 'Policy Pet',
            'sex' => 'unknown',
            'neutering_status' => 'unknown',
        ]);
        $case = ShelterCase::query()->create([
            'pet_profile_id' => $pet->id,
            'user_id' => $owner->id,
            'case_type' => 'rescue',
            'title' => 'Policy case',
        ]);
        $consultation = PetCareConsultation::query()->create([
            'pet_profile_id' => $pet->id,
            'user_id' => $owner->id,
            'subject' => 'Policy consultation',
        ]);

        return [$ticket, $pet, $case, $consultation];
    }

    private function user(string $accessLevel): User
    {
        return User::factory()
            ->accessLevel($accessLevel)
            ->create()
            ->refresh();
    }

    private function actingAsWithQaContext(User $user): void
    {
        if (! request()->hasSession()) {
            request()->setLaravelSession(app('session')->driver());
        }

        $this->actingAs($user);
        request()->session()->put([
            'myapes.authentication_method' => 'qa',
            'myapes.authorization_epoch' => $user->authorization_epoch,
        ]);
    }
}
