<?php

namespace Tests\Feature;

use App\Exceptions\ModuleLifecycleException;
use App\Models\ModuleInstallation;
use App\Models\PetProfile;
use App\Models\User;
use App\Services\ModuleInstallationSynchronizer;
use App\Services\ModuleInstanceLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ModuleStateMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ModuleInstallationSynchronizer::class)->synchronize();
    }

    public function test_every_existing_module_route_uses_the_authoritative_state_middleware(): void
    {
        $expected = [
            'apes-cic.tickets.' => 'module.available:apes-cic,tickets',
            'shelter.pets.' => 'module.available:shelter-rescue,pet-profiles',
            'shelter.cases.' => 'module.available:shelter-rescue,cases',
            'petcare.pets.' => 'module.available:pet-care-clinic,pet-profiles',
            'petcare.consultations.' => 'module.available:pet-care-clinic,consultations',
        ];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if (! is_string($name)) {
                continue;
            }

            foreach ($expected as $prefix => $middleware) {
                if (str_starts_with($name, $prefix)) {
                    $this->assertContains(
                        $middleware,
                        $route->gatherMiddleware(),
                        "Route [{$name}] is not protected by module state.",
                    );
                }
            }
        }
    }

    public function test_enabled_routes_retain_the_existing_feature_authorization_behavior(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/apes-cic/tickets')
            ->assertOk();
        $this->actingAs($user)
            ->get('/shelter/pets')
            ->assertOk();
        $this->actingAs($user)
            ->get('/petcare/consultations')
            ->assertOk();
    }

    public function test_disabled_module_routes_return_the_same_non_disclosing_not_found_response(): void
    {
        $user = User::factory()->create();
        ModuleInstallation::query()
            ->where('sub_core_key', 'apes-cic')
            ->where('module_key', 'tickets')
            ->update([
                'enabled' => false,
                'disabled_at' => now(),
                'updated_at' => now(),
            ]);

        $get = $this->actingAs($user)->get('/apes-cic/tickets');
        $post = $this->actingAs($user)->post('/apes-cic/tickets', []);

        $get->assertNotFound();
        $post->assertNotFound();
        $this->assertSame($get->getStatusCode(), $post->getStatusCode());
    }

    public function test_disabled_module_remains_not_found_when_the_service_is_unselected(): void
    {
        $user = User::factory()->create();
        $user->serviceSelections()->where('sub_core_key', 'apes-cic')->delete();
        ModuleInstallation::query()
            ->where('sub_core_key', 'apes-cic')
            ->where('module_key', 'tickets')
            ->update([
                'enabled' => false,
                'disabled_at' => now(),
                'updated_at' => now(),
            ]);

        $this->actingAs($user)
            ->get('/apes-cic/tickets')
            ->assertNotFound();
    }

    public function test_a_busy_module_write_returns_the_same_non_disclosing_not_found_response(): void
    {
        $this->mock(ModuleInstanceLock::class, function ($mock): void {
            $mock->shouldReceive('run')
                ->once()
                ->andThrow(new ModuleLifecycleException('instance_busy'));
        });
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/apes-cic/tickets', [])
            ->assertNotFound();
    }

    public function test_shared_pet_profile_bindings_cannot_cross_sub_core_boundaries(): void
    {
        $user = User::factory()->create();
        $shelterPet = PetProfile::query()->create([
            'user_id' => $user->id,
            'service_domain' => PetProfile::DOMAIN_SHELTER,
            'name' => 'Shelter profile',
            'species' => 'bird',
            'sex' => 'unknown',
            'neutering_status' => 'unknown',
        ]);
        $petCarePet = PetProfile::query()->create([
            'user_id' => $user->id,
            'service_domain' => PetProfile::DOMAIN_PETCARE,
            'name' => 'Pet Care profile',
            'species' => 'reptile',
            'sex' => 'unknown',
            'neutering_status' => 'unknown',
        ]);

        $this->actingAs($user)
            ->get(route('shelter.pets.show', $petCarePet))
            ->assertNotFound();
        $this->actingAs($user)
            ->get(route('petcare.pets.show', $shelterPet))
            ->assertNotFound();
    }
}
