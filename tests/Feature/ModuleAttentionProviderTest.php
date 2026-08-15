<?php

namespace Tests\Feature;

use App\Contracts\ModuleAttentionProvider;
use App\Contracts\ModuleRegistry;
use App\Models\PetCareConsultation;
use App\Models\PetProfile;
use App\Models\ShelterCase;
use App\Models\SupportTicket;
use App\Models\User;
use App\Modules\ModuleAttentionItem;
use App\Services\AuthorizationProfile;
use App\Services\ModuleInstallationSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ModuleAttentionProviderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ModuleInstallationSynchronizer::class)->synchronize();
    }

    public function test_ticket_attention_is_instance_visible_open_typed_and_limited(): void
    {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        $olderApes = $this->ticketFor($owner, [
            'subject' => 'APES attention ticket',
            'closed_at' => Carbon::parse('2026-08-14 08:00:00'),
            'updated_at' => Carbon::parse('2026-08-14 09:00:00'),
        ]);
        $newerApes = $this->ticketFor($owner, [
            'subject' => 'Newest APES attention ticket',
            'updated_at' => Carbon::parse('2026-08-14 10:00:00'),
        ]);
        $this->ticketFor($owner, [
            'subject' => 'Resolved APES ticket',
            'status' => 'resolved',
        ]);
        $this->ticketFor($owner, [
            'subject' => 'Closed APES ticket',
            'status' => 'closed',
        ]);
        $this->ticketFor($otherOwner, [
            'subject' => 'Other owner APES ticket',
        ]);
        $shelter = $this->ticketFor($owner, [
            'sub_core_key' => 'shelter-rescue',
            'service_area' => 'rescue',
            'subject' => 'Shelter attention ticket',
            'updated_at' => Carbon::parse('2026-08-14 11:00:00'),
        ]);
        $petCare = $this->ticketFor($owner, [
            'sub_core_key' => 'pet-care-clinic',
            'service_area' => 'appointment',
            'subject' => 'Pet Care attention ticket',
            'updated_at' => Carbon::parse('2026-08-14 12:00:00'),
        ]);

        $registry = app(ModuleRegistry::class);
        $apesInstance = $registry->instance('apes-cic', 'tickets');
        $shelterInstance = $registry->instance('shelter-rescue', 'tickets');
        $petCareInstance = $registry->instance('pet-care-clinic', 'tickets');
        $this->actingAs($owner);
        /** @var ModuleAttentionProvider $provider */
        $provider = app($apesInstance->attentionProviderClass());
        $apesItems = $provider->attention($apesInstance, $owner, 6);
        $limited = $provider->attention($apesInstance, $owner, 1);
        $shelterItems = $provider->attention($shelterInstance, $owner, 6);
        $petCareItems = $provider->attention($petCareInstance, $owner, 6);

        $this->assertSame(
            ['Newest APES attention ticket', 'APES attention ticket'],
            array_map(
                static fn (ModuleAttentionItem $item): string => $item->title,
                $apesItems,
            ),
        );
        $this->assertSame([$newerApes->id], array_map(
            static fn (ModuleAttentionItem $item): int => $item->recordId,
            $limited,
        ));
        $this->assertSame('apes-cic:tickets', $apesItems[0]->instanceKey);
        $this->assertSame('ticket', $apesItems[0]->type);
        $this->assertSame('ticket', $apesItems[0]->icon);
        $this->assertSame('APES CIC', $apesItems[0]->service);
        $this->assertSame('Ticket', $apesItems[0]->label);
        $this->assertSame('high', $apesItems[0]->priority);
        $this->assertSame('apes-cic.tickets.show', $apesItems[0]->routeName);
        $this->assertSame($olderApes->id, $apesItems[1]->recordId);
        $this->assertCount(1, $shelterItems);
        $this->assertSame('shelter-rescue:tickets', $shelterItems[0]->instanceKey);
        $this->assertSame('APES Shelter and Rescue', $shelterItems[0]->service);
        $this->assertSame('shelter.tickets.show', $shelterItems[0]->routeName);
        $this->assertSame($shelter->id, $shelterItems[0]->recordId);
        $this->assertCount(1, $petCareItems);
        $this->assertSame('pet-care-clinic:tickets', $petCareItems[0]->instanceKey);
        $this->assertSame('APES Pet Care Clinic', $petCareItems[0]->service);
        $this->assertSame('petcare.tickets.show', $petCareItems[0]->routeName);
        $this->assertSame($petCare->id, $petCareItems[0]->recordId);
    }

    public function test_case_attention_preserves_instance_open_semantics_copy_and_links(): void
    {
        $owner = User::factory()->create(['name' => 'Attention case owner']);
        $otherOwner = User::factory()->create();
        $apes = $this->caseFor($owner, [
            'title' => 'APES attention case',
            'updated_at' => Carbon::parse('2026-08-14 09:00:00'),
        ]);
        $this->caseFor($owner, [
            'title' => 'Resolved APES attention case',
            'status' => 'resolved',
        ]);
        $this->caseFor($owner, [
            'title' => 'Closed APES attention case',
            'status' => 'closed',
        ]);
        $shelterPet = $this->shelterPetFor($owner, 'Attention Shelter pet');
        $shelter = $this->caseFor($owner, [
            'sub_core_key' => ShelterCase::SUB_CORE_SHELTER_RESCUE,
            'pet_profile_id' => $shelterPet->id,
            'case_type' => 'rescue',
            'category' => null,
            'title' => 'Shelter attention case',
            'updated_at' => Carbon::parse('2026-08-14 10:00:00'),
        ]);
        $this->caseFor($owner, [
            'sub_core_key' => ShelterCase::SUB_CORE_SHELTER_RESCUE,
            'pet_profile_id' => $shelterPet->id,
            'case_type' => 'fostering',
            'category' => null,
            'status' => 'closed',
            'title' => 'Closed Shelter attention case',
        ]);
        $otherPet = $this->shelterPetFor($otherOwner, 'Other owner pet');
        $this->caseFor($otherOwner, [
            'sub_core_key' => ShelterCase::SUB_CORE_SHELTER_RESCUE,
            'pet_profile_id' => $otherPet->id,
            'case_type' => 'adoption',
            'category' => null,
            'title' => 'Other owner Shelter attention case',
        ]);

        $registry = app(ModuleRegistry::class);
        $apesInstance = $registry->instance('apes-cic', 'cases');
        $shelterInstance = $registry->instance('shelter-rescue', 'cases');
        $this->actingAs($owner);
        /** @var ModuleAttentionProvider $provider */
        $provider = app($apesInstance->attentionProviderClass());
        $apesItems = $provider->attention($apesInstance, $owner);
        $shelterItems = $provider->attention($shelterInstance, $owner);

        $this->assertCount(1, $apesItems);
        $this->assertSame('apes-cic:cases', $apesItems[0]->instanceKey);
        $this->assertSame('ticket', $apesItems[0]->type);
        $this->assertSame('briefcase-business', $apesItems[0]->icon);
        $this->assertSame('APES CIC', $apesItems[0]->service);
        $this->assertSame('Case', $apesItems[0]->label);
        $this->assertSame('APES attention case', $apesItems[0]->title);
        $this->assertSame('medium', $apesItems[0]->priority);
        $this->assertSame('General', $apesItems[0]->context);
        $this->assertSame('For: Attention case owner', $apesItems[0]->owner);
        $this->assertSame('apes-cic.cases.show', $apesItems[0]->routeName);
        $this->assertSame($apes->id, $apesItems[0]->recordId);

        $this->assertCount(1, $shelterItems);
        $this->assertSame('shelter-rescue:cases', $shelterItems[0]->instanceKey);
        $this->assertSame('shelter', $shelterItems[0]->type);
        $this->assertSame('house', $shelterItems[0]->icon);
        $this->assertSame('APES Shelter', $shelterItems[0]->service);
        $this->assertSame('Shelter attention case', $shelterItems[0]->title);
        $this->assertNull($shelterItems[0]->priority);
        $this->assertSame('Rescue', $shelterItems[0]->context);
        $this->assertSame('Pet: Attention Shelter pet', $shelterItems[0]->owner);
        $this->assertSame('shelter.cases.show', $shelterItems[0]->routeName);
        $this->assertSame($shelter->id, $shelterItems[0]->recordId);
    }

    public function test_consultation_attention_requires_exact_visibility_and_uses_clinic_open_links(): void
    {
        Carbon::setTestNow('2026-08-14 10:00:00');
        $owner = User::factory()->create(['name' => 'Consultation owner']);
        $otherOwner = User::factory()->create();
        $revokedStaff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create();
        $exactStaff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_ADMINISTRATOR)
            ->create();
        $pet = $this->petCarePetFor($owner, 'Attention clinic pet');
        $otherPet = $this->petCarePetFor($otherOwner, 'Other clinic pet');
        $consultation = $this->consultationFor($owner, $pet, [
            'subject' => 'Open attention consultation',
            'scheduled_for' => now()->addHours(3),
            'updated_at' => now()->subMinute(),
        ]);
        $closedConsultation = $this->consultationFor($owner, $pet, [
            'subject' => 'Closed attention consultation',
            'status' => 'closed',
        ]);
        $otherConsultation = $this->consultationFor($otherOwner, $otherPet, [
            'subject' => 'Other owner consultation',
        ]);
        $this->removeRolePermission(
            AuthorizationProfile::ROLE_SERVICE_USER,
            'pet-care-clinic.consultations.view-own',
        );
        $this->removeRolePermission(
            AuthorizationProfile::ROLE_STAFF,
            'pet-care-clinic.consultations.view-all',
        );

        $instance = app(ModuleRegistry::class)
            ->instance('pet-care-clinic', 'consultations');
        /** @var ModuleAttentionProvider $provider */
        $provider = app($instance->attentionProviderClass());
        $owner = $owner->fresh();
        $this->actingAs($owner);
        $this->assertFalse($owner->can('pet-care-clinic.consultations.view-own'));
        $ownerItems = $provider->attention($instance, $owner);

        $this->assertSame([], $ownerItems);

        $revokedStaff = $revokedStaff->fresh();
        $this->actingAs($revokedStaff);
        $this->assertTrue($revokedStaff->can(AuthorizationProfile::PERMISSION_STAFF_ACCESS));
        $this->assertFalse($revokedStaff->can('pet-care-clinic.consultations.view-all'));
        $this->assertSame([], PetCareConsultation::query()
            ->visibleTo($revokedStaff)
            ->pluck('id')
            ->all());
        $this->assertSame([], $provider->attention($instance, $revokedStaff));

        $this->actingAs($exactStaff);
        $exactItems = $provider->attention($instance, $exactStaff);
        $this->assertCount(2, $exactItems);
        $this->assertSame(
            ['APES Pet Care Clinic'],
            collect($exactItems)->pluck('service')->unique()->values()->all(),
        );
        $this->assertSame(
            ['petcare.consultations.show'],
            collect($exactItems)->pluck('routeName')->unique()->values()->all(),
        );
    }

    private function ticketFor(User $owner, array $attributes = []): SupportTicket
    {
        $timestamps = array_intersect_key(
            $attributes,
            array_flip(['created_at', 'updated_at']),
        );
        $ticket = SupportTicket::query()->create(array_merge([
            'sub_core_key' => SupportTicket::SUB_CORE_APES_CIC,
            'user_id' => $owner->id,
            'service_area' => 'it',
            'subject' => 'Attention ticket fixture',
            'priority' => 'high',
            'status' => 'open',
            'description' => 'Attention provider fixture.',
        ], $attributes));

        if ($timestamps !== []) {
            $ticket->timestamps = false;
            $ticket->forceFill($timestamps)->saveQuietly();
            $ticket->timestamps = true;
        }

        return $ticket->fresh();
    }

    private function caseFor(User $owner, array $attributes = []): ShelterCase
    {
        $timestamps = array_intersect_key(
            $attributes,
            array_flip(['created_at', 'updated_at']),
        );
        $case = ShelterCase::query()->create(array_merge([
            'sub_core_key' => ShelterCase::SUB_CORE_APES_CIC,
            'pet_profile_id' => null,
            'user_id' => $owner->id,
            'case_type' => null,
            'category' => 'general',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Attention case fixture',
            'details' => 'Attention provider fixture.',
        ], $attributes));

        if ($timestamps !== []) {
            $case->timestamps = false;
            $case->forceFill($timestamps)->saveQuietly();
            $case->timestamps = true;
        }

        return $case->fresh();
    }

    private function shelterPetFor(User $owner, string $name): PetProfile
    {
        return PetProfile::query()->create([
            'user_id' => $owner->id,
            'service_domain' => PetProfile::DOMAIN_SHELTER,
            'name' => $name,
            'species' => 'dog',
            'sex' => 'unknown',
            'neutering_status' => 'unknown',
        ]);
    }

    private function petCarePetFor(User $owner, string $name): PetProfile
    {
        return PetProfile::query()->create([
            'user_id' => $owner->id,
            'service_domain' => PetProfile::DOMAIN_PETCARE,
            'name' => $name,
            'species' => 'cat',
            'sex' => 'unknown',
            'neutering_status' => 'unknown',
        ]);
    }

    private function consultationFor(
        User $owner,
        PetProfile $pet,
        array $attributes = [],
    ): PetCareConsultation {
        $timestamps = array_intersect_key(
            $attributes,
            array_flip(['created_at', 'updated_at']),
        );
        $consultation = PetCareConsultation::query()->create(array_merge([
            'pet_profile_id' => $pet->id,
            'user_id' => $owner->id,
            'subject' => 'Attention consultation fixture',
            'status' => 'open',
            'notes' => 'Attention provider fixture.',
        ], $attributes));

        if ($timestamps !== []) {
            $consultation->timestamps = false;
            $consultation->forceFill($timestamps)->saveQuietly();
            $consultation->timestamps = true;
        }

        return $consultation->fresh();
    }

    private function removeRolePermission(
        string $roleName,
        string $permissionName,
    ): void {
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
