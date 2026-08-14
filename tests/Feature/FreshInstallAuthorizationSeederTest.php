<?php

namespace Tests\Feature;

use App\Models\CaseUpdate;
use App\Models\Role;
use App\Models\RoleSource;
use App\Models\ShelterCase;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\AuthorizationAccountSynchronizer;
use App\Services\AuthorizationProfile;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Database\Seeders\LocalQaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class FreshInstallAuthorizationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_factory_materializes_a_provenanced_service_user_baseline(): void
    {
        $user = User::factory()->create();
        $serviceRole = Role::query()
            ->where('guard_name', 'web')
            ->where('name', AuthorizationProfile::ROLE_SERVICE_USER)
            ->firstOrFail();

        $this->assertSame(User::ROLE_SERVICE_USER, $user->accessLevel());
        $this->assertDatabaseHas('role_sources', [
            'user_id' => $user->id,
            'role_id' => $serviceRole->id,
            'source' => RoleSource::SOURCE_SYSTEM,
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'model_type' => User::class,
            'model_id' => $user->id,
            'role_id' => $serviceRole->id,
        ]);
        $this->assertDatabaseCount('model_has_permissions', 0);
    }

    public function test_phase_b_factory_helpers_create_protected_custom_directory_and_context_fixtures(): void
    {
        $this->assertTrue(
            method_exists(UserFactory::class, 'phaseAAccessLevel')
                && method_exists(UserFactory::class, 'protectedRole')
                && method_exists(UserFactory::class, 'customRole')
                && method_exists(UserFactory::class, 'directoryIdentity')
                && method_exists(UserFactory::class, 'authorizationContextEpoch'),
            'The explicit Phase A and Phase B User factory helpers are missing.',
        );

        $administrator = User::factory()
            ->protectedRole(
                AuthorizationProfile::ROLE_ADMINISTRATOR,
                RoleSource::SOURCE_SYSTEM,
            )
            ->create();
        $custom = User::factory()
            ->customRole('qa-case-reviewer')
            ->create();
        $directory = User::factory()
            ->directoryIdentity('qa-directory-subject')
            ->create();
        $context = User::factory()
            ->authorizationContextEpoch(7)
            ->create();

        $this->assertSame(User::ROLE_ADMIN, $administrator->accessLevel());
        $this->assertDatabaseHas('role_sources', [
            'user_id' => $administrator->id,
            'source' => RoleSource::SOURCE_SYSTEM,
        ]);
        $this->assertDatabaseHas('role_sources', [
            'user_id' => $custom->id,
            'source' => RoleSource::SOURCE_LOCAL,
        ]);
        $this->assertSame(
            ['qa-case-reviewer', AuthorizationProfile::ROLE_SERVICE_USER],
            $custom->roles()->orderBy('name')->pluck('name')->all(),
        );
        $this->assertSame(User::IDENTITY_CLOUDRON_OIDC, $directory->identity_type);
        $this->assertSame('qa-directory-subject', $directory->oidc_sub);
        $this->assertSame([], $directory->ldap_groups);
        $this->assertSame(7, $context->authorization_epoch);
        $this->assertDatabaseCount('model_has_permissions', 0);
    }

    public function test_local_qa_seeding_is_idempotent_and_contains_only_local_provenanced_fixtures(): void
    {
        $this->assertTrue(
            method_exists(LocalQaSeeder::class, 'emails'),
            'The deterministic local QA user catalogue is missing.',
        );

        $seededAt = CarbonImmutable::parse(
            '2026-07-24 09:00:00',
            'Europe/London',
        )->utc();
        $sentinelOwner = User::factory()->create([
            'email' => LocalQaSeeder::SERVICE_USER_EMAIL,
        ]);
        $collisionSentinel = SupportTicket::query()->create([
            'sub_core_key' => SupportTicket::SUB_CORE_APES_CIC,
            'user_id' => $sentinelOwner->id,
            'assigned_to' => null,
            'service_area' => 'operations',
            'subject' => 'QA Seed: Shelter adoption enquiry',
            'priority' => 'low',
            'status' => 'resolved',
            'description' => 'Cross-sub-core natural-key collision sentinel.',
            'closed_at' => $seededAt->subDays(20),
        ]);
        $collisionSentinel->forceFill([
            'created_at' => $seededAt->subDays(22),
            'updated_at' => $seededAt->subDays(21),
        ])->saveQuietly();

        $this->seed(LocalQaSeeder::class);

        $firstUserIds = User::query()
            ->whereIn('email', LocalQaSeeder::emails())
            ->orderBy('email')
            ->pluck('id', 'email')
            ->all();
        $firstFixtureSnapshot = $this->localQaTicketAndCaseSnapshot();

        $this->seed(LocalQaSeeder::class);

        $this->assertSame(
            $firstUserIds,
            User::query()
                ->whereIn('email', LocalQaSeeder::emails())
                ->orderBy('email')
                ->pluck('id', 'email')
                ->all(),
            'Repeated local QA seeding changed one of the four deterministic user identities.',
        );
        $this->assertSame(
            $firstFixtureSnapshot,
            $this->localQaTicketAndCaseSnapshot(),
            'Repeated local QA seeding changed Ticket/Case parent or child IDs, values, or timestamps.',
        );

        $users = User::query()
            ->whereIn('email', LocalQaSeeder::emails())
            ->orderBy('email')
            ->get();

        $this->assertCount(4, $users);
        $this->assertSame(
            [User::IDENTITY_LOCAL],
            $users->pluck('identity_type')->unique()->values()->all(),
        );
        $this->assertSame([null], $users->pluck('oidc_sub')->unique()->values()->all());
        $this->assertSame([[]], $users->pluck('ldap_groups')->unique()->values()->all());

        foreach ($users as $user) {
            $protectedRoles = $user->roles()
                ->where('guard_name', 'web')
                ->where('is_protected', true)
                ->pluck('name');
            $this->assertCount(1, $protectedRoles);
        }

        $unprovenancedPivotCount = DB::table('model_has_roles')
            ->where('model_type', User::class)
            ->whereIn('model_id', $users->pluck('id'))
            ->whereNotExists(function ($query): void {
                $query
                    ->selectRaw('1')
                    ->from('role_sources')
                    ->whereColumn('role_sources.user_id', 'model_has_roles.model_id')
                    ->whereColumn('role_sources.role_id', 'model_has_roles.role_id');
            })
            ->count();
        $this->assertSame(0, $unprovenancedPivotCount);
        $this->assertDatabaseCount('model_has_permissions', 0);

        $customRole = Role::query()
            ->where('guard_name', 'web')
            ->where('name', LocalQaSeeder::CUSTOM_ROLE_NAME)
            ->firstOrFail();
        $this->assertFalse($customRole->is_protected);
        $this->assertSame(1, Role::query()
            ->where('guard_name', 'web')
            ->where('name', LocalQaSeeder::CUSTOM_ROLE_NAME)
            ->count());
        $this->assertDatabaseHas('role_sources', [
            'user_id' => $users
                ->firstWhere('email', LocalQaSeeder::STAFF_EMAIL)
                ?->id,
            'role_id' => $customRole->id,
            'source' => RoleSource::SOURCE_LOCAL,
        ]);

        $encodedGroups = strtolower((string) json_encode(
            $users->pluck('ldap_groups')->all(),
        ));
        foreach ([
            'position.staff',
            'position.students',
            'position.volunteers',
            'intranet.administrator',
            'intranet.superadmin',
        ] as $legacyAlias) {
            $this->assertStringNotContainsString($legacyAlias, $encodedGroups);
        }

        $serviceUser = $users->firstWhere('email', LocalQaSeeder::SERVICE_USER_EMAIL);
        $staffUser = $users->firstWhere('email', LocalQaSeeder::STAFF_EMAIL);
        $adminUser = $users->firstWhere('email', LocalQaSeeder::ADMIN_EMAIL);

        $apesTickets = SupportTicket::query()
            ->forSubCore(SupportTicket::SUB_CORE_APES_CIC)
            ->where('user_id', $serviceUser?->id)
            ->whereIn('subject', [
                'QA Seed: IT account support request',
                'QA Seed: Documentation follow-up',
                'QA Seed: HR policy clarification',
            ])
            ->orderBy('subject')
            ->get();
        $this->assertSame([
            [
                'subject' => 'QA Seed: Documentation follow-up',
                'sub_core_key' => SupportTicket::SUB_CORE_APES_CIC,
                'user_id' => $serviceUser?->id,
                'assigned_to' => $staffUser?->id,
                'service_area' => 'legal',
                'priority' => 'medium',
                'status' => 'open',
            ],
            [
                'subject' => 'QA Seed: HR policy clarification',
                'sub_core_key' => SupportTicket::SUB_CORE_APES_CIC,
                'user_id' => $serviceUser?->id,
                'assigned_to' => $adminUser?->id,
                'service_area' => 'human_resources',
                'priority' => 'medium',
                'status' => 'resolved',
            ],
            [
                'subject' => 'QA Seed: IT account support request',
                'sub_core_key' => SupportTicket::SUB_CORE_APES_CIC,
                'user_id' => $serviceUser?->id,
                'assigned_to' => $staffUser?->id,
                'service_area' => 'it',
                'priority' => 'high',
                'status' => 'in_progress',
            ],
        ], $apesTickets->map->only([
            'subject',
            'sub_core_key',
            'user_id',
            'assigned_to',
            'service_area',
            'priority',
            'status',
        ])->all());

        $sentinel = SupportTicket::query()->findOrFail($collisionSentinel->id);
        $this->assertSame([
            SupportTicket::SUB_CORE_APES_CIC,
            $serviceUser?->id,
            null,
            'operations',
            'QA Seed: Shelter adoption enquiry',
            'low',
            'resolved',
            'Cross-sub-core natural-key collision sentinel.',
        ], [
            $sentinel->sub_core_key,
            $sentinel->user_id,
            $sentinel->assigned_to,
            $sentinel->service_area,
            $sentinel->subject,
            $sentinel->priority,
            $sentinel->status,
            $sentinel->description,
        ]);
        $this->assertTrue($sentinel->created_at->equalTo($seededAt->subDays(22)));
        $this->assertTrue($sentinel->updated_at->equalTo($seededAt->subDays(21)));
        $this->assertTrue($sentinel->closed_at->equalTo($seededAt->subDays(20)));

        $shelterTickets = SupportTicket::query()
            ->forSubCore(ShelterCase::SUB_CORE_SHELTER_RESCUE)
            ->where('user_id', $serviceUser?->id)
            ->whereIn('subject', [
                'QA Seed: Shelter adoption enquiry',
                'QA Seed: Shelter rescue coordination',
                'QA Seed: Shelter welfare follow-up',
            ])
            ->orderBy('subject')
            ->get()
            ->keyBy('subject');
        $this->assertCount(3, $shelterTickets);
        $this->assertSame([
            ShelterCase::SUB_CORE_SHELTER_RESCUE,
            $serviceUser?->id,
            null,
            'adoption',
            'low',
            'open',
            null,
        ], [
            $shelterTickets->get('QA Seed: Shelter adoption enquiry')?->sub_core_key,
            $shelterTickets->get('QA Seed: Shelter adoption enquiry')?->user_id,
            $shelterTickets->get('QA Seed: Shelter adoption enquiry')?->assigned_to,
            $shelterTickets->get('QA Seed: Shelter adoption enquiry')?->service_area,
            $shelterTickets->get('QA Seed: Shelter adoption enquiry')?->priority,
            $shelterTickets->get('QA Seed: Shelter adoption enquiry')?->status,
            $shelterTickets->get('QA Seed: Shelter adoption enquiry')?->closed_at,
        ]);
        $this->assertSame([
            ShelterCase::SUB_CORE_SHELTER_RESCUE,
            $serviceUser?->id,
            $staffUser?->id,
            'rescue',
            'high',
            'in_progress',
            null,
        ], [
            $shelterTickets->get('QA Seed: Shelter rescue coordination')?->sub_core_key,
            $shelterTickets->get('QA Seed: Shelter rescue coordination')?->user_id,
            $shelterTickets->get('QA Seed: Shelter rescue coordination')?->assigned_to,
            $shelterTickets->get('QA Seed: Shelter rescue coordination')?->service_area,
            $shelterTickets->get('QA Seed: Shelter rescue coordination')?->priority,
            $shelterTickets->get('QA Seed: Shelter rescue coordination')?->status,
            $shelterTickets->get('QA Seed: Shelter rescue coordination')?->closed_at,
        ]);
        $closedShelterTicket = $shelterTickets->get('QA Seed: Shelter welfare follow-up');
        $this->assertSame([
            ShelterCase::SUB_CORE_SHELTER_RESCUE,
            $serviceUser?->id,
            $adminUser?->id,
            'animal_welfare',
            'medium',
            'closed',
        ], [
            $closedShelterTicket?->sub_core_key,
            $closedShelterTicket?->user_id,
            $closedShelterTicket?->assigned_to,
            $closedShelterTicket?->service_area,
            $closedShelterTicket?->priority,
            $closedShelterTicket?->status,
        ]);
        $this->assertTrue($closedShelterTicket?->closed_at->equalTo($seededAt->subDays(4)));
        $this->assertSame(
            ['adoption', 'animal_welfare', 'rescue'],
            $shelterTickets->pluck('service_area')->sort()->values()->all(),
        );

        $ticketMessageFixtures = [
            'QA Seed: Shelter adoption enquiry' => [
                [
                    'user_id' => $serviceUser?->id,
                    'message' => 'Could you explain the adoption home-check steps?',
                    'is_staff_note' => false,
                ],
                [
                    'user_id' => $staffUser?->id,
                    'message' => 'Internal note: confirm adopter information before follow-up.',
                    'is_staff_note' => true,
                ],
            ],
            'QA Seed: Shelter rescue coordination' => [
                [
                    'user_id' => $serviceUser?->id,
                    'message' => 'A transport volunteer is available for the rescue intake.',
                    'is_staff_note' => false,
                ],
                [
                    'user_id' => $staffUser?->id,
                    'message' => 'Internal note: coordinating transport and temporary foster space.',
                    'is_staff_note' => true,
                ],
            ],
            'QA Seed: Shelter welfare follow-up' => [
                [
                    'user_id' => $serviceUser?->id,
                    'message' => 'Thank you for confirming the welfare follow-up.',
                    'is_staff_note' => false,
                ],
                [
                    'user_id' => $adminUser?->id,
                    'message' => 'Internal note: welfare follow-up is complete.',
                    'is_staff_note' => true,
                ],
            ],
        ];
        foreach ($ticketMessageFixtures as $subject => $expectedMessages) {
            $this->assertSame(
                $expectedMessages,
                $shelterTickets->get($subject)?->messages()
                    ->orderBy('id')
                    ->get(['user_id', 'message', 'is_staff_note'])
                    ->map->only(['user_id', 'message', 'is_staff_note'])
                    ->all(),
            );
        }
        $this->assertSame(4, SupportTicket::query()
            ->forSubCore(SupportTicket::SUB_CORE_APES_CIC)
            ->where('user_id', $serviceUser?->id)
            ->count());
        $this->assertSame(3, SupportTicket::query()
            ->forSubCore(ShelterCase::SUB_CORE_SHELTER_RESCUE)
            ->where('user_id', $serviceUser?->id)
            ->count());
        $this->assertDatabaseCount('support_ticket_messages', 12);

        $cases = ShelterCase::query()
            ->forSubCore(ShelterCase::SUB_CORE_APES_CIC)
            ->where('user_id', $serviceUser?->id)
            ->whereIn('title', [
                'QA Seed: Reopened membership support review',
                'QA Seed: Welfare response required',
                'QA Seed: Closed operations complaint',
            ])
            ->get()
            ->keyBy('title');

        $this->assertCount(3, $cases);
        $reopened = $cases->get('QA Seed: Reopened membership support review');
        $waiting = $cases->get('QA Seed: Welfare response required');
        $closed = $cases->get('QA Seed: Closed operations complaint');
        $this->assertNotNull($reopened);
        $this->assertNotNull($waiting);
        $this->assertNotNull($closed);
        $this->assertSame([
            'membership',
            'high',
            'in_progress',
            $staffUser?->id,
            null,
            null,
        ], [
            $reopened->category,
            $reopened->priority,
            $reopened->status,
            $reopened->assigned_to,
            $reopened->resolved_at,
            $reopened->closed_at,
        ]);
        $this->assertSame([
            'welfare',
            'urgent',
            'waiting_on_user',
            $staffUser?->id,
        ], [
            $waiting->category,
            $waiting->priority,
            $waiting->status,
            $waiting->assigned_to,
        ]);
        $this->assertSame([
            'complaint',
            'low',
            'closed',
            $adminUser?->id,
        ], [
            $closed->category,
            $closed->priority,
            $closed->status,
            $closed->assigned_to,
        ]);
        foreach ($cases as $case) {
            $this->assertSame(ShelterCase::SUB_CORE_APES_CIC, $case->sub_core_key);
            $this->assertSame($serviceUser?->id, $case->user_id);
            $this->assertNull($case->pet_profile_id);
            $this->assertNull($case->case_type);
        }
        $this->assertTrue($reopened->opened_at->equalTo($seededAt->subDays(5)));
        $this->assertTrue($waiting->opened_at->equalTo($seededAt->subDays(3)));
        $this->assertTrue($closed->opened_at->equalTo($seededAt->subDays(8)));
        $this->assertTrue($closed->resolved_at->equalTo($seededAt->subDays(3)));
        $this->assertTrue($closed->closed_at->equalTo($seededAt->subDays(2)));
        $this->assertSame([
            [
                'user_id' => $serviceUser?->id,
                'body' => 'Additional information received; case reopened for review.',
                'visibility' => CaseUpdate::VISIBILITY_PUBLIC,
            ],
            [
                'user_id' => $staffUser?->id,
                'body' => 'Internal triage note for local visibility testing.',
                'visibility' => CaseUpdate::VISIBILITY_INTERNAL,
            ],
        ], $reopened->updates()
            ->orderBy('id')
            ->get(['user_id', 'body', 'visibility'])
            ->map->only(['user_id', 'body', 'visibility'])
            ->all());
        $this->assertSame([
            [
                'user_id' => $adminUser?->id,
                'body' => 'Outcome shared with the service user.',
                'visibility' => CaseUpdate::VISIBILITY_PUBLIC,
            ],
        ], $closed->updates()
            ->orderBy('id')
            ->get(['user_id', 'body', 'visibility'])
            ->map->only(['user_id', 'body', 'visibility'])
            ->all());

        $shelterCases = ShelterCase::query()
            ->forSubCore(ShelterCase::SUB_CORE_SHELTER_RESCUE)
            ->where('user_id', $serviceUser?->id)
            ->whereIn('title', [
                'QA Seed: Mango rescue intake',
                'QA Seed: Mango foster placement review',
                'QA Seed: Mango adoption follow-up',
            ])
            ->get()
            ->keyBy('title');
        $this->assertCount(3, $shelterCases);
        $shelterPetIds = $shelterCases->pluck('pet_profile_id')->unique()->values();
        $this->assertCount(1, $shelterPetIds);
        $this->assertNotNull($shelterPetIds->first());
        $this->assertSame([
            'rescue',
            'in_review',
            $staffUser?->id,
            null,
        ], [
            $shelterCases->get('QA Seed: Mango rescue intake')?->case_type,
            $shelterCases->get('QA Seed: Mango rescue intake')?->status,
            $shelterCases->get('QA Seed: Mango rescue intake')?->assigned_to,
            $shelterCases->get('QA Seed: Mango rescue intake')?->closed_at,
        ]);
        $this->assertSame([
            'fostering',
            'open',
            $adminUser?->id,
            null,
        ], [
            $shelterCases->get('QA Seed: Mango foster placement review')?->case_type,
            $shelterCases->get('QA Seed: Mango foster placement review')?->status,
            $shelterCases->get('QA Seed: Mango foster placement review')?->assigned_to,
            $shelterCases->get('QA Seed: Mango foster placement review')?->closed_at,
        ]);
        $closedShelterCase = $shelterCases->get('QA Seed: Mango adoption follow-up');
        $this->assertSame([
            'adoption',
            'closed',
            $adminUser?->id,
        ], [
            $closedShelterCase?->case_type,
            $closedShelterCase?->status,
            $closedShelterCase?->assigned_to,
        ]);
        $this->assertTrue($closedShelterCase?->closed_at->equalTo($seededAt->subDays(2)));
        foreach ($shelterCases as $case) {
            $this->assertSame(ShelterCase::SUB_CORE_SHELTER_RESCUE, $case->sub_core_key);
            $this->assertSame($serviceUser?->id, $case->user_id);
            $this->assertSame($shelterPetIds->first(), $case->pet_profile_id);
        }

        $rescueCase = $shelterCases->get('QA Seed: Mango rescue intake');
        $this->assertSame([
            [
                'user_id' => $serviceUser?->id,
                'body' => 'Mango is settled and ready for the next rescue assessment.',
                'visibility' => CaseUpdate::VISIBILITY_PUBLIC,
            ],
            [
                'user_id' => $staffUser?->id,
                'body' => 'Internal note: confirm foster placement before closing intake.',
                'visibility' => CaseUpdate::VISIBILITY_INTERNAL,
            ],
        ], $rescueCase?->updates()
            ->orderBy('id')
            ->get(['user_id', 'body', 'visibility'])
            ->map->only(['user_id', 'body', 'visibility'])
            ->all());
        $this->assertTrue($rescueCase?->updated_at->equalTo($seededAt->subMinutes(5)));
        $this->assertDatabaseCount('case_updates', 5);
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function localQaTicketAndCaseSnapshot(): array
    {
        return [
            'tickets' => DB::table('support_tickets')
                ->orderBy('id')
                ->get()
                ->map(static fn (object $row): array => (array) $row)
                ->all(),
            'ticket_messages' => DB::table('support_ticket_messages')
                ->orderBy('id')
                ->get()
                ->map(static fn (object $row): array => (array) $row)
                ->all(),
            'cases' => DB::table('shelter_cases')
                ->orderBy('id')
                ->get()
                ->map(static fn (object $row): array => (array) $row)
                ->all(),
            'case_updates' => DB::table('case_updates')
                ->orderBy('id')
                ->get()
                ->map(static fn (object $row): array => (array) $row)
                ->all(),
        ];
    }

    public function test_local_qa_baselines_survive_activation_synchronization_and_pass_integrity_checks(): void
    {
        $this->seed(LocalQaSeeder::class);

        $this->artisan('myapes:authorization-sync')
            ->assertSuccessful();

        $expectedProtectedRoles = [
            LocalQaSeeder::SERVICE_USER_EMAIL => AuthorizationProfile::ROLE_SERVICE_USER,
            LocalQaSeeder::STAFF_EMAIL => AuthorizationProfile::ROLE_STAFF,
            LocalQaSeeder::ADMIN_EMAIL => AuthorizationProfile::ROLE_ADMINISTRATOR,
            LocalQaSeeder::SUPERADMIN_EMAIL => AuthorizationProfile::ROLE_SUPER_ADMIN,
        ];

        foreach ($expectedProtectedRoles as $email => $expectedRole) {
            $user = User::query()->where('email', $email)->firstOrFail();
            $protectedRoles = $user->roles()
                ->where('guard_name', 'web')
                ->where('is_protected', true)
                ->orderBy('name')
                ->pluck('name')
                ->all();

            $this->assertSame([$expectedRole], $protectedRoles);
            $this->assertSame(
                $expectedRole,
                app(AuthorizationProfile::class)->effectiveProtectedRole($user),
            );
        }

        $staffUser = User::query()
            ->where('email', LocalQaSeeder::STAFF_EMAIL)
            ->firstOrFail();
        $this->assertTrue(
            $staffUser->roles()
                ->where('guard_name', 'web')
                ->where('name', LocalQaSeeder::CUSTOM_ROLE_NAME)
                ->exists(),
        );
        $this->assertDatabaseHas('role_sources', [
            'user_id' => $staffUser->id,
            'role_id' => Role::query()
                ->where('guard_name', 'web')
                ->where('name', LocalQaSeeder::CUSTOM_ROLE_NAME)
                ->value('id'),
            'source' => RoleSource::SOURCE_LOCAL,
        ]);

        $this->artisan('myapes:authorization-check')
            ->assertSuccessful();
    }

    public function test_local_emulation_is_reduced_to_public_baseline_outside_local_and_testing(): void
    {
        $this->seed(LocalQaSeeder::class);
        $staffUser = User::query()
            ->where('email', LocalQaSeeder::STAFF_EMAIL)
            ->firstOrFail();

        $this->app->detectEnvironment(fn (): string => 'production');
        app(AuthorizationAccountSynchronizer::class)
            ->grantPublicBaseline($staffUser);

        $staffUser->refresh();
        $this->assertSame(User::ROLE_SERVICE_USER, $staffUser->accessLevel());
        $this->assertSame(
            [AuthorizationProfile::ROLE_SERVICE_USER],
            $staffUser->roles()
                ->where('guard_name', 'web')
                ->where('is_protected', true)
                ->pluck('name')
                ->all(),
        );
        $this->assertTrue(
            $staffUser->roles()
                ->where('guard_name', 'web')
                ->where('name', LocalQaSeeder::CUSTOM_ROLE_NAME)
                ->exists(),
        );
    }

    public function test_direct_local_qa_seeding_is_denied_in_production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Local QA fixtures are unavailable outside local and testing environments.',
        );

        (new LocalQaSeeder)->run();
    }
}
