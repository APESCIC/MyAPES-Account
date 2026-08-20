<?php

namespace Tests\Feature;

use App\Models\ModuleInstallation;
use App\Models\PetCareConsultation;
use App\Models\PetProfile;
use App\Models\ShelterCase;
use App\Models\SupportTicket;
use App\Models\User;
use App\Modules\ModuleAttentionItem;
use App\Services\AuthorizationProfile;
use App\Services\ModuleInstallationSynchronizer;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ViewErrorBag;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardAttentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ModuleInstallationSynchronizer::class)->synchronize();
    }

    public function test_dashboard_uses_typed_providers_and_globally_limits_visible_instances(): void
    {
        $owner = User::factory()->create();
        $shelterPet = PetProfile::query()->create([
            'user_id' => $owner->id,
            'service_domain' => PetProfile::DOMAIN_SHELTER,
            'name' => 'Shelter dashboard pet',
            'species' => 'dog',
            'sex' => 'unknown',
            'neutering_status' => 'unknown',
        ]);
        $carePet = $this->petFor($owner);

        $newestShelterTicket = SupportTicket::query()->create([
            'sub_core_key' => 'shelter-rescue',
            'user_id' => $owner->id,
            'service_area' => 'rescue',
            'subject' => 'Newest Shelter ticket',
            'priority' => 'urgent',
            'status' => 'open',
            'description' => 'Typed dashboard fixture.',
        ]);
        $consultation = PetCareConsultation::query()->create([
            'pet_profile_id' => $carePet->id,
            'user_id' => $owner->id,
            'subject' => 'Second consultation',
            'status' => 'open',
            'notes' => 'Typed dashboard fixture.',
        ]);
        $shelterCase = ShelterCase::query()->create([
            'sub_core_key' => 'shelter-rescue',
            'pet_profile_id' => $shelterPet->id,
            'user_id' => $owner->id,
            'case_type' => 'rescue',
            'status' => 'in_review',
            'title' => 'Third Shelter case',
            'details' => 'Typed dashboard fixture.',
        ]);
        $apesCase = ShelterCase::query()->create([
            'sub_core_key' => 'apes-cic',
            'user_id' => $owner->id,
            'category' => 'general',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Fourth APES case',
            'details' => 'Typed dashboard fixture.',
        ]);
        $apesTicket = $this->ticketFor($owner, 'Fifth APES ticket');
        $olderShelterTicket = SupportTicket::query()->create([
            'sub_core_key' => 'shelter-rescue',
            'user_id' => $owner->id,
            'service_area' => 'fostering',
            'subject' => 'Sixth Shelter ticket',
            'priority' => 'medium',
            'status' => 'open',
            'description' => 'Typed dashboard fixture.',
        ]);
        $excludedApesTicket = $this->ticketFor($owner, 'Seventh APES ticket');

        foreach ([
            [$newestShelterTicket, 7],
            [$consultation, 6],
            [$shelterCase, 5],
            [$apesCase, 4],
            [$apesTicket, 3],
            [$olderShelterTicket, 2],
            [$excludedApesTicket, 1],
        ] as [$record, $hour]) {
            $this->setUpdatedAt(
                $record,
                now()->startOfDay()->addHours($hour),
            );
        }
        $this->setUpdatedAt($shelterPet, now()->startOfDay()->addHours(8));

        $response = $this->actingAs($owner)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('attentionItems', function (array $items): bool {
            return collect($items)->every(
                static fn ($item): bool => $item instanceof ModuleAttentionItem,
            ) && array_map(
                static fn (ModuleAttentionItem $item): string => $item->title,
                $items,
            ) === [
                'Newest Shelter ticket',
                'Second consultation',
                'Third Shelter case',
                'Fourth APES case',
                'Fifth APES ticket',
                'Sixth Shelter ticket',
            ];
        });
        $response
            ->assertSee('APES Shelter and Rescue · Ticket')
            ->assertSee(route('shelter.tickets.show', $newestShelterTicket))
            ->assertSee(route('petcare.consultations.show', $consultation))
            ->assertSee(route('shelter.cases.show', $shelterCase))
            ->assertSee(route('apes-cic.cases.show', $apesCase))
            ->assertSee(route('apes-cic.tickets.show', $apesTicket))
            ->assertDontSee($excludedApesTicket->subject);
    }

    public function test_dashboard_attention_excludes_disabled_and_unauthorized_instances_while_profiles_stay_summary_only(): void
    {
        $owner = User::factory()->create();
        $apesTicket = $this->ticketFor($owner, 'Visible enabled APES ticket');
        $shelterTicket = SupportTicket::query()->create([
            'sub_core_key' => 'shelter-rescue',
            'user_id' => $owner->id,
            'service_area' => 'rescue',
            'subject' => 'Disabled Shelter ticket',
            'priority' => 'high',
            'status' => 'open',
            'description' => 'Must be excluded with its module.',
        ]);
        $apesCase = ShelterCase::query()->create([
            'sub_core_key' => 'apes-cic',
            'user_id' => $owner->id,
            'category' => 'general',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Unauthorized APES case',
            'details' => 'Must be excluded without exact view permission.',
        ]);
        $profile = PetProfile::query()->create([
            'user_id' => $owner->id,
            'service_domain' => PetProfile::DOMAIN_SHELTER,
            'name' => 'Summary-only Shelter profile',
            'species' => 'dog',
            'sex' => 'unknown',
            'neutering_status' => 'unknown',
        ]);
        ModuleInstallation::query()
            ->where('sub_core_key', 'shelter-rescue')
            ->where('module_key', 'tickets')
            ->update([
                'enabled' => false,
                'disabled_at' => now(),
            ]);
        $this->removeRolePermission(
            AuthorizationProfile::ROLE_SERVICE_USER,
            'apes-cic.cases.view-own',
        );

        $response = $this->actingAs($owner->fresh())->get(route('dashboard'));

        $response->assertOk()
            ->assertSee($apesTicket->subject)
            ->assertDontSee($shelterTicket->subject)
            ->assertDontSee($apesCase->title)
            ->assertDontSee($profile->name);
        $response->assertViewHas(
            'attentionItems',
            static fn (array $items): bool => array_map(
                static fn (ModuleAttentionItem $item): string => $item->instanceKey,
                $items,
            ) === ['apes-cic:tickets'],
        );
        $response->assertViewHas(
            'moduleSummaries',
            static fn (array $groups): bool => collect($groups)
                ->flatMap(static fn ($group) => $group->summaries)
                ->contains(
                    static fn ($summary): bool => $summary->instanceKey
                            === 'shelter-rescue:pet-profiles'
                        && $summary->total === 1,
                ),
        );
    }

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
        $response->assertViewHas(
            'moduleSummaries',
            static fn (array $groups): bool => collect($groups)
                ->flatMap(static fn ($group) => $group->summaries)
                ->contains(
                    static fn ($summary): bool => $summary->instanceKey === 'apes-cic:tickets'
                        && $summary->total === 8,
                ),
        );
        $response->assertViewHas('attentionItems', function (array $items) use ($expectedTitles): bool {
            return count($items) === 6
                && array_column($items, 'title') === array_slice($expectedTitles, 0, 6);
        });
        $response->assertDontSee('Closed ticket');
        $response->assertDontSee('Another user ticket');
    }

    public function test_staff_attention_feed_includes_open_items_across_users_and_services(): void
    {
        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create();
        $firstUser = User::factory()->accessLevel(User::ROLE_SERVICE_USER)->create();
        $secondUser = User::factory()->accessLevel(User::ROLE_SERVICE_USER)->create();
        $firstPet = $this->shelterPetFor($firstUser);
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
            return array_map(
                static fn (ModuleAttentionItem $item): string => $item->title,
                $items,
            ) === [
                'Cross-service consultation',
                'Cross-service shelter case',
                'Cross-service ticket',
            ];
        });
    }

    public function test_admin_attention_feed_includes_normalized_open_items_for_all_users(): void
    {
        $admin = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_ADMINISTRATOR)
            ->create();
        $serviceUser = User::factory()->accessLevel(User::ROLE_SERVICE_USER)->create();
        $ticket = $this->ticketFor($serviceUser, 'Admin-visible ticket');
        $this->setUpdatedAt($ticket, now()->subMinute());

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('attentionItems', function (array $items): bool {
            return count($items) === 1
                && $items[0] instanceof ModuleAttentionItem
                && $items[0]->title === 'Admin-visible ticket'
                && $items[0]->type === 'ticket'
                && $items[0]->status === 'open'
                && $items[0]->routeName === 'apes-cic.tickets.show';
        });
    }

    public function test_dashboard_renders_an_attention_item_created_with_datetime_immutable(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $item = new ModuleAttentionItem(
            instanceKey: 'apes-cic:tickets',
            type: 'ticket',
            icon: 'ticket',
            service: 'APES CIC',
            label: 'Ticket',
            title: 'Native immutable attention item',
            status: 'open',
            priority: 'medium',
            context: null,
            owner: $user->name,
            updatedAt: new DateTimeImmutable('2026-08-14T12:34:56+01:00'),
            routeName: 'apes-cic.tickets.show',
            recordId: 42,
        );

        $html = view('dashboard', [
            'attentionItems' => [$item],
            'moduleSummaries' => [],
            'errors' => new ViewErrorBag,
        ])->render();

        $this->assertStringContainsString(
            'Native immutable attention item',
            $html,
        );
        $this->assertStringContainsString(
            'datetime="2026-08-14T12:34:56+01:00"',
            $html,
        );
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

    private function shelterPetFor(User $user): PetProfile
    {
        return PetProfile::create([
            'user_id' => $user->id,
            'service_domain' => PetProfile::DOMAIN_SHELTER,
            'name' => 'QA Shelter Pet '.$user->id,
            'species' => 'Dog',
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
