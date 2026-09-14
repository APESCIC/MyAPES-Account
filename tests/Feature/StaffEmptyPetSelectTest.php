<?php

namespace Tests\Feature;

use App\Models\PetProfile;
use App\Models\User;
use App\Services\AuthorizationProfile;
use App\Services\ModuleInstallationSynchronizer;
use App\Support\StaffPetCreateReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StaffEmptyPetSelectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ModuleInstallationSynchronizer::class)->synchronize();
    }

    /**
     * @return array<string, array{0: array{
     *     createIndexRoute: string,
     *     petsIndexRoute: string,
     *     petsStoreRoute: string,
     *     returnKey: string,
     *     createPetPermission: string,
     *     domain: string
     * }}>
     */
    public static function staffCreateFlows(): array
    {
        return [
            'shelter cases' => [[
                'createIndexRoute' => 'shelter.cases.index',
                'petsIndexRoute' => 'shelter.pets.index',
                'petsStoreRoute' => 'shelter.pets.store',
                'returnKey' => StaffPetCreateReturn::SHELTER_CASES,
                'createPetPermission' => 'shelter-rescue.pet-profiles.create',
                'domain' => PetProfile::DOMAIN_SHELTER,
            ]],
            'pet care consultations' => [[
                'createIndexRoute' => 'petcare.consultations.index',
                'petsIndexRoute' => 'petcare.pets.index',
                'petsStoreRoute' => 'petcare.pets.store',
                'returnKey' => StaffPetCreateReturn::PETCARE_CONSULTATIONS,
                'createPetPermission' => 'pet-care-clinic.pet-profiles.create',
                'domain' => PetProfile::DOMAIN_PETCARE,
            ]],
        ];
    }

    /**
     * @param  array{
     *     createIndexRoute: string,
     *     petsIndexRoute: string,
     *     petsStoreRoute: string,
     *     returnKey: string,
     *     createPetPermission: string,
     *     domain: string
     * }  $flow
     */
    #[DataProvider('staffCreateFlows')]
    public function test_staff_empty_pet_select_explains_the_gap_and_returns_after_adding_a_pet(
        array $flow,
    ): void {
        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create();
        $addPetUrl = route($flow['petsIndexRoute'], [
            'return_to' => $flow['returnKey'],
        ]).'#create';

        $this->actingAs($staff)
            ->get(route($flow['createIndexRoute']))
            ->assertOk()
            ->assertSee('data-empty-pet-select', false)
            ->assertSeeText('No pet profiles are available yet.')
            ->assertSeeText('Add a pet first')
            ->assertSee('href="'.$addPetUrl.'"', false)
            ->assertDontSee('name="pet_profile_id"', false);

        $this->get(route($flow['petsIndexRoute'], [
            'return_to' => $flow['returnKey'],
        ]))
            ->assertOk()
            ->assertSee('name="return_to"', false)
            ->assertSee('value="'.$flow['returnKey'].'"', false)
            ->assertSeeText('After you save this pet, you will return to the form you started.');

        $response = $this->post(route($flow['petsStoreRoute']), [
            'name' => 'Return-path pet',
            'sex' => 'unknown',
            'neutering_status' => 'unknown',
            'return_to' => $flow['returnKey'],
        ]);

        $pet = PetProfile::query()->where('name', 'Return-path pet')->firstOrFail();
        $response->assertRedirect(route($flow['createIndexRoute'], [
            'pet_profile_id' => $pet->id,
        ]).'#create');
        $this->assertSame($staff->id, $pet->user_id);
        $this->assertSame($flow['domain'], $pet->service_domain);

        $this->get(route($flow['createIndexRoute'], [
            'pet_profile_id' => $pet->id,
        ]))
            ->assertOk()
            ->assertDontSee('data-empty-pet-select', false)
            ->assertSee('name="pet_profile_id"', false)
            ->assertSee('value="'.$pet->id.'" selected', false)
            ->assertSeeText('Return-path pet');
    }

    /**
     * @param  array{
     *     createIndexRoute: string,
     *     petsIndexRoute: string,
     *     petsStoreRoute: string,
     *     returnKey: string,
     *     createPetPermission: string,
     *     domain: string
     * }  $flow
     */
    #[DataProvider('staffCreateFlows')]
    public function test_public_empty_pet_select_stays_a_blank_dropdown_without_add_pet_copy(
        array $flow,
    ): void {
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->get(route($flow['createIndexRoute']))
            ->assertOk()
            ->assertSee('name="pet_profile_id"', false)
            ->assertDontSee('data-empty-pet-select', false)
            ->assertDontSeeText('Add a pet first')
            ->assertDontSee('return_to='.$flow['returnKey']);
    }

    /**
     * @param  array{
     *     createIndexRoute: string,
     *     petsIndexRoute: string,
     *     petsStoreRoute: string,
     *     returnKey: string,
     *     createPetPermission: string,
     *     domain: string
     * }  $flow
     */
    #[DataProvider('staffCreateFlows')]
    public function test_staff_with_pets_keep_the_dropdown_and_do_not_see_the_empty_state(
        array $flow,
    ): void {
        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create();
        $pet = PetProfile::query()->create([
            'user_id' => $staff->id,
            'service_domain' => $flow['domain'],
            'name' => 'Existing staff pet',
            'species' => 'dog',
            'sex' => 'unknown',
            'neutering_status' => 'unknown',
        ]);

        $this->actingAs($staff)
            ->get(route($flow['createIndexRoute']))
            ->assertOk()
            ->assertDontSee('data-empty-pet-select', false)
            ->assertDontSeeText('Add a pet first')
            ->assertSee('name="pet_profile_id"', false)
            ->assertSeeText('Existing staff pet')
            ->assertSee('value="'.$pet->id.'"', false);
    }

    /**
     * @param  array{
     *     createIndexRoute: string,
     *     petsIndexRoute: string,
     *     petsStoreRoute: string,
     *     returnKey: string,
     *     createPetPermission: string,
     *     domain: string
     * }  $flow
     */
    #[DataProvider('staffCreateFlows')]
    public function test_staff_without_pet_create_permission_still_see_the_empty_state_without_the_add_link(
        array $flow,
    ): void {
        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create();
        $this->removeRolePermission(
            AuthorizationProfile::ROLE_STAFF,
            $flow['createPetPermission'],
        );

        $this->actingAs($staff->fresh())
            ->get(route($flow['createIndexRoute']))
            ->assertOk()
            ->assertSee('data-empty-pet-select', false)
            ->assertSeeText('No pet profiles are available yet.')
            ->assertDontSee('data-add-pet-first', false)
            ->assertDontSeeText('Add a pet first');
    }

    public function test_unsafe_return_to_does_not_redirect_away_from_the_new_pet(): void
    {
        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create();

        $response = $this->actingAs($staff)->post(route('shelter.pets.store'), [
            'name' => 'Unsafe return pet',
            'sex' => 'unknown',
            'neutering_status' => 'unknown',
            'return_to' => 'https://example.invalid/phish',
        ]);

        $pet = PetProfile::query()->where('name', 'Unsafe return pet')->firstOrFail();
        $response->assertRedirect(route('shelter.pets.show', $pet));
    }

    private function removeRolePermission(string $roleName, string $permissionName): void
    {
        $role = Role::query()
            ->where('guard_name', 'web')
            ->where('name', $roleName)
            ->firstOrFail();
        $permission = Permission::query()
            ->where('guard_name', 'web')
            ->where('name', $permissionName)
            ->firstOrFail();
        $role->permissions()->detach($permission->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
