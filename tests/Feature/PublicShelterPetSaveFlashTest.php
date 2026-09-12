<?php

namespace Tests\Feature;

use App\Models\PetProfile;
use App\Models\User;
use App\Services\ModuleInstallationSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicShelterPetSaveFlashTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ModuleInstallationSynchronizer::class)->synchronize();
    }

    public function test_public_shelter_pet_create_shows_a_success_flash(): void
    {
        $owner = User::factory()->create();

        $response = $this->actingAs($owner)
            ->post(route('shelter.pets.store'), [
                'name' => 'Flash create pet',
                'species' => 'dog',
                'sex' => 'unknown',
                'neutering_status' => 'unknown',
            ]);

        $pet = PetProfile::query()
            ->where('name', 'Flash create pet')
            ->firstOrFail();

        $response->assertRedirect(route('shelter.pets.show', $pet))
            ->assertSessionHas('status', 'Your pet has been saved.');

        $this->assertSame($owner->id, $pet->user_id);
        $this->assertSame(PetProfile::DOMAIN_SHELTER, $pet->service_domain);

        $this->get(route('shelter.pets.show', $pet))
            ->assertOk()
            ->assertSee('Your pet has been saved.')
            ->assertDontSee('record updated', false)
            ->assertDontSee('workflow', false);
    }

    public function test_public_shelter_pet_save_shows_a_success_flash(): void
    {
        $owner = User::factory()->create();
        $pet = PetProfile::query()->create([
            'user_id' => $owner->id,
            'service_domain' => PetProfile::DOMAIN_SHELTER,
            'name' => 'Flash save pet',
            'species' => 'cat',
            'sex' => 'unknown',
            'neutering_status' => 'unknown',
        ]);

        $this->actingAs($owner)
            ->put(route('shelter.pets.update', $pet), [
                'name' => 'Flash save pet renamed',
                'species' => 'cat',
                'sex' => 'unknown',
                'neutering_status' => 'unknown',
            ])
            ->assertRedirect(route('shelter.pets.show', $pet))
            ->assertSessionHas('status', 'Your pet has been saved.');

        $this->assertSame('Flash save pet renamed', $pet->fresh()->name);

        $this->get(route('shelter.pets.show', $pet))
            ->assertOk()
            ->assertSee('Your pet has been saved.')
            ->assertDontSee('Pet profile updated.');
    }
}
