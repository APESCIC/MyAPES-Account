<?php

namespace Tests\Feature;

use App\Models\PetCareConsultation;
use App\Models\PetProfile;
use App\Models\ShelterCase;
use App\Models\User;
use App\Services\ModuleInstallationSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicShelterPetPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ModuleInstallationSynchronizer::class)->synchronize();
    }

    public function test_public_shelter_pet_page_shows_owner_created_date_and_id(): void
    {
        $owner = User::factory()->create([
            'name' => 'Public Pet Owner',
            'email' => 'public-pet-owner@example.test',
        ]);
        $pet = $this->shelterPetFor($owner, 'Willow', [
            'created_at' => '2026-01-15 12:00:00',
            'updated_at' => '2026-01-15 12:00:00',
        ]);

        $this->actingAs($owner)
            ->get(route('shelter.pets.show', $pet))
            ->assertOk()
            ->assertSeeInOrder([
                'Owner',
                'Public Pet Owner',
                'public-pet-owner@example.test',
                'Created',
                '15/01/2026',
                'ID',
                '#'.$pet->id,
            ]);
    }

    public function test_public_shelter_pet_page_escapes_owner_name_markup(): void
    {
        $owner = User::factory()->create([
            'name' => 'Owner <script>alert(1)</script>',
            'email' => 'escaped-owner@example.test',
        ]);
        $pet = $this->shelterPetFor($owner, 'Ash');

        $this->actingAs($owner)
            ->get(route('shelter.pets.show', $pet))
            ->assertOk()
            ->assertSee('Owner <script>alert(1)</script>')
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_public_shelter_pet_page_does_not_link_to_cases_or_clinic_records(): void
    {
        $owner = User::factory()->create([
            'name' => 'Linked Record Owner',
        ]);
        $pet = $this->shelterPetFor($owner, 'Bramble');
        $case = ShelterCase::query()->create([
            'sub_core_key' => ShelterCase::SUB_CORE_SHELTER_RESCUE,
            'pet_profile_id' => $pet->id,
            'user_id' => $owner->id,
            'case_type' => 'rescue',
            'status' => 'open',
            'title' => 'Bramble rescue case',
            'details' => 'Linked Shelter case that must not appear as a pet-page link.',
        ]);
        $clinicPet = PetProfile::query()->create([
            'user_id' => $owner->id,
            'service_domain' => PetProfile::DOMAIN_PETCARE,
            'name' => 'Clinic Bramble',
            'species' => 'dog',
            'sex' => 'unknown',
            'neutering_status' => 'unknown',
        ]);
        $consultation = PetCareConsultation::query()->create([
            'pet_profile_id' => $clinicPet->id,
            'user_id' => $owner->id,
            'subject' => 'Clinic follow-up',
            'status' => 'open',
            'notes' => 'Clinic record that must not appear as a pet-page link.',
        ]);

        $this->actingAs($owner)
            ->get(route('shelter.pets.show', $pet))
            ->assertOk()
            ->assertDontSee(route('shelter.cases.show', $case), false)
            ->assertDontSee(route('petcare.consultations.show', $consultation), false)
            ->assertDontSee(route('petcare.pets.show', $clinicPet), false)
            ->assertDontSee('Bramble rescue case')
            ->assertDontSee('Clinic follow-up');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function shelterPetFor(User $owner, string $name, array $attributes = []): PetProfile
    {
        $pet = PetProfile::query()->create(array_merge([
            'user_id' => $owner->id,
            'service_domain' => PetProfile::DOMAIN_SHELTER,
            'name' => $name,
            'species' => 'dog',
            'sex' => 'unknown',
            'neutering_status' => 'unknown',
        ], $attributes));

        if (isset($attributes['created_at']) || isset($attributes['updated_at'])) {
            $pet->timestamps = false;
            $pet->forceFill(array_intersect_key(
                $attributes,
                array_flip(['created_at', 'updated_at']),
            ))->saveQuietly();
            $pet->timestamps = true;
        }

        return $pet->fresh();
    }
}
