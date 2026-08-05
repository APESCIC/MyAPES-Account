<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DirectoryGroup;
use App\Models\Permission;
use App\Models\PetCareConsultation;
use App\Models\PetProfile;
use App\Models\Role;
use App\Models\RoleSource;
use App\Models\ShelterCase;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\ConsultationUpdatedNotification;
use App\Notifications\ShelterCaseUpdatedNotification;
use App\Notifications\TicketUpdatedNotification;
use App\Services\AuthorizationProfile;
use App\Services\AuthorizationRoleMaterializer;
use App\Services\DirectoryRoleSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SecurityRemediationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_updates_preserve_assignments_when_the_field_is_omitted(): void
    {
        Notification::fake();
        $owner = $this->user(User::ROLE_SERVICE_USER);
        $staff = $this->user(User::ROLE_STAFF);
        [$ticket, $case, $consultation] = $this->records(
            $owner,
            $staff,
        );

        $this->actingAs($owner)->put(
            route('apes-cic.tickets.update', $ticket),
            ['status' => 'open', 'priority' => 'high', 'message' => null],
        )->assertRedirect();
        $this->actingAs($owner)->put(
            route('shelter.cases.update', $case),
            ['status' => 'in_review', 'details' => 'Owner update'],
        )->assertRedirect();
        $this->actingAs($owner)->put(
            route('petcare.consultations.update', $consultation),
            ['status' => 'in_progress', 'notes' => 'Owner update'],
        )->assertRedirect();

        $this->assertSame($staff->id, $ticket->fresh()->assigned_to);
        $this->assertSame($staff->id, $case->fresh()->assigned_to);
        $this->assertSame(
            $staff->id,
            $consultation->fresh()->assigned_to,
        );
    }

    public function test_owner_explicit_assignment_probes_have_one_forbidden_response_and_sanitized_audit(): void
    {
        Notification::fake();
        $owner = $this->user(User::ROLE_SERVICE_USER);
        $staff = $this->user(User::ROLE_STAFF);
        [$ticket, $case, $consultation] = $this->records($owner, $staff);
        $workflows = [
            [
                'route' => route('apes-cic.tickets.update', $ticket),
                'payload' => [
                    'status' => 'open',
                    'priority' => 'medium',
                    'message' => null,
                ],
                'record' => $ticket,
            ],
            [
                'route' => route('shelter.cases.update', $case),
                'payload' => [
                    'status' => 'in_review',
                    'details' => 'Owner update',
                ],
                'record' => $case,
            ],
            [
                'route' => route(
                    'petcare.consultations.update',
                    $consultation,
                ),
                'payload' => [
                    'status' => 'in_progress',
                    'notes' => 'Owner update',
                ],
                'record' => $consultation,
            ],
        ];

        foreach ($workflows as $workflow) {
            $existing = $this->actingAs($owner)->put(
                $workflow['route'],
                [...$workflow['payload'], 'assigned_to' => $staff->id],
            );
            $missing = $this->actingAs($owner)->put(
                $workflow['route'],
                [...$workflow['payload'], 'assigned_to' => 999999],
            );
            $unassign = $this->actingAs($owner)->put(
                $workflow['route'],
                [...$workflow['payload'], 'assigned_to' => null],
            );

            foreach ([$existing, $missing, $unassign] as $response) {
                $response->assertForbidden();
            }
            $this->assertSame(
                $existing->getContent(),
                $missing->getContent(),
            );
            $this->assertSame(
                $existing->getContent(),
                $unassign->getContent(),
            );
            $this->assertSame(
                $staff->id,
                $workflow['record']->fresh()->assigned_to,
            );
        }

        $audits = AuditLog::query()
            ->where('event', 'authorization.assignment_denied')
            ->get();
        $this->assertCount(9, $audits);
        foreach ($audits as $audit) {
            $contextKeys = array_keys($audit->context);
            sort($contextKeys);
            $this->assertSame(
                ['actor_id', 'method', 'reason_code', 'route_name'],
                $contextKeys,
            );
        }
        $this->assertStringNotContainsString(
            (string) $staff->id,
            $audits->pluck('context')->toJson(),
        );
    }

    public function test_staff_assignment_validation_hides_why_an_assignee_is_unavailable(): void
    {
        Notification::fake();
        $owner = $this->user(User::ROLE_SERVICE_USER);
        $actor = $this->user(User::ROLE_STAFF);
        $ordinary = $this->user(User::ROLE_SERVICE_USER);
        $suspended = $this->user(User::ROLE_STAFF, [
            'suspended_at' => now(),
            'suspension_reason' => 'Security hold',
        ]);
        $permissionOnly = $this->permissionOnlyUser();
        [$ticket, $case, $consultation] = $this->records($owner, $actor);
        $workflows = [
            [
                'route' => route('apes-cic.tickets.update', $ticket),
                'payload' => [
                    'status' => 'open',
                    'priority' => 'medium',
                    'message' => null,
                ],
                'record' => $ticket,
            ],
            [
                'route' => route('shelter.cases.update', $case),
                'payload' => [
                    'status' => 'in_review',
                    'details' => 'Staff update',
                ],
                'record' => $case,
            ],
            [
                'route' => route(
                    'petcare.consultations.update',
                    $consultation,
                ),
                'payload' => [
                    'status' => 'in_progress',
                    'notes' => 'Staff update',
                ],
                'record' => $consultation,
            ],
        ];

        foreach ($workflows as $workflow) {
            foreach ([
                999999,
                $ordinary->id,
                $suspended->id,
                $permissionOnly->id,
            ] as $candidate) {
                $this->actingAs($actor)
                    ->put($workflow['route'], [
                        ...$workflow['payload'],
                        'assigned_to' => $candidate,
                    ])
                    ->assertRedirect()
                    ->assertSessionHasErrors([
                        'assigned_to' => 'The selected assignee is unavailable.',
                    ]);
            }
        }

        $replacement = $this->user(User::ROLE_STAFF);
        foreach ($workflows as $workflow) {
            $this->actingAs($actor)->put(
                $workflow['route'],
                [
                    ...$workflow['payload'],
                    'assigned_to' => $replacement->id,
                ],
            )->assertRedirect();
            $this->assertSame(
                $replacement->id,
                $workflow['record']->fresh()->assigned_to,
            );

            $this->actingAs($actor)->put(
                $workflow['route'],
                [...$workflow['payload'], 'assigned_to' => null],
            )->assertRedirect();
            $this->assertNull(
                $workflow['record']->fresh()->assigned_to,
            );
        }
    }

    public function test_all_notification_paths_exclude_permission_only_or_suspended_recipients(): void
    {
        Notification::fake();
        $owner = $this->user(User::ROLE_SERVICE_USER);
        $actor = $this->user(User::ROLE_STAFF);
        $eligibleRecipient = $this->user(User::ROLE_STAFF);
        $permissionOnly = $this->permissionOnlyUser();
        $suspended = $this->user(User::ROLE_STAFF, [
            'suspended_at' => now(),
            'suspension_reason' => 'Security hold',
        ]);
        [$ticket, $case, $consultation] = $this->records(
            $owner,
            $permissionOnly,
        );

        $this->actingAs($actor)->put(
            route('apes-cic.tickets.update', $ticket),
            ['status' => 'open', 'priority' => 'medium', 'message' => null],
        )->assertRedirect();
        $this->actingAs($actor)->put(
            route('shelter.cases.update', $case),
            ['status' => 'in_review', 'details' => 'Staff update'],
        )->assertRedirect();
        $this->actingAs($actor)->put(
            route('petcare.consultations.update', $consultation),
            ['status' => 'in_progress', 'notes' => 'Staff update'],
        )->assertRedirect();

        Notification::assertNotSentTo(
            $permissionOnly,
            TicketUpdatedNotification::class,
        );
        Notification::assertNotSentTo(
            $permissionOnly,
            ShelterCaseUpdatedNotification::class,
        );
        Notification::assertNotSentTo(
            $permissionOnly,
            ConsultationUpdatedNotification::class,
        );
        Notification::assertNothingSentTo($suspended);
        Notification::assertSentTo(
            $eligibleRecipient,
            TicketUpdatedNotification::class,
        );
        Notification::assertSentTo(
            $eligibleRecipient,
            ShelterCaseUpdatedNotification::class,
        );
        Notification::assertSentTo(
            $eligibleRecipient,
            ConsultationUpdatedNotification::class,
        );
        Notification::assertSentTo($owner, TicketUpdatedNotification::class);
        Notification::assertSentTo(
            $owner,
            ShelterCaseUpdatedNotification::class,
        );
        Notification::assertSentTo(
            $owner,
            ConsultationUpdatedNotification::class,
        );
    }

    public function test_permission_only_owner_cannot_see_or_change_assignment_controls_or_staff_identities(): void
    {
        $owner = $this->permissionOnlyUser();
        $staff = $this->user(User::ROLE_STAFF, [
            'name' => 'Hidden eligible staff identity',
        ]);
        [$ticket, $case, $consultation] = $this->records($owner, $staff);

        foreach ([
            [
                'show' => route('apes-cic.tickets.show', $ticket),
                'update' => route('apes-cic.tickets.update', $ticket),
                'payload' => [
                    'status' => 'open',
                    'priority' => 'medium',
                    'message' => null,
                ],
                'record' => $ticket,
            ],
            [
                'show' => route('shelter.cases.show', $case),
                'update' => route('shelter.cases.update', $case),
                'payload' => [
                    'status' => 'in_review',
                    'details' => 'Owner update',
                ],
                'record' => $case,
            ],
            [
                'show' => route(
                    'petcare.consultations.show',
                    $consultation,
                ),
                'update' => route(
                    'petcare.consultations.update',
                    $consultation,
                ),
                'payload' => [
                    'status' => 'in_progress',
                    'notes' => 'Owner update',
                ],
                'record' => $consultation,
            ],
        ] as $workflow) {
            $this->actingAs($owner)
                ->get($workflow['show'])
                ->assertOk()
                ->assertDontSee('name="assigned_to"', false)
                ->assertDontSee($staff->name);
            $this->actingAs($owner)
                ->put($workflow['update'], [
                    ...$workflow['payload'],
                    'assigned_to' => $staff->id,
                ])
                ->assertForbidden();
            $this->assertSame(
                $staff->id,
                $workflow['record']->fresh()->assigned_to,
            );
        }
    }

    public function test_directory_revocation_with_a_retained_custom_permission_removes_assignment_eligibility(): void
    {
        Notification::fake();
        $revoked = $this->permissionOnlyUser();
        $directoryGroup = DirectoryGroup::query()
            ->where('name', 'myapes.staff')
            ->firstOrFail();
        $directoryGroup->forceFill([
            'member_count' => 1,
            'status' => DirectoryGroup::STATUS_PRESENT,
        ])->save();
        $staffRole = Role::query()
            ->where('guard_name', 'web')
            ->where('name', AuthorizationProfile::ROLE_STAFF)
            ->firstOrFail();
        app(AuthorizationRoleMaterializer::class)->grant(
            $revoked,
            $staffRole,
            RoleSource::SOURCE_DIRECTORY,
            $directoryGroup,
        );

        app(DirectoryRoleSynchronizer::class)->revoke($revoked);
        $revoked->refresh();

        $this->assertTrue(
            $revoked->roles()
                ->where('roles.is_protected', false)
                ->whereHas(
                    'permissions',
                    static fn ($permissionQuery) => $permissionQuery
                        ->where(
                            'permissions.name',
                            AuthorizationProfile::PERMISSION_STAFF_ACCESS,
                        ),
                )
                ->exists(),
        );
        $this->assertFalse(
            User::query()
                ->eligibleStaff()
                ->whereKey($revoked->id)
                ->exists(),
        );

        $owner = $this->user(User::ROLE_SERVICE_USER);
        $actor = $this->user(User::ROLE_STAFF);
        $assignee = $this->user(User::ROLE_STAFF, [
            'name' => 'Directory-eligible assignment identity',
        ]);
        [$ticket, $case, $consultation] = $this->records(
            $owner,
            $assignee,
        );
        $workflows = [
            [
                'show' => route('apes-cic.tickets.show', $ticket),
                'update' => route('apes-cic.tickets.update', $ticket),
                'payload' => [
                    'status' => 'open',
                    'priority' => 'medium',
                    'message' => null,
                ],
                'record' => $ticket,
            ],
            [
                'show' => route('shelter.cases.show', $case),
                'update' => route('shelter.cases.update', $case),
                'payload' => [
                    'status' => 'in_review',
                    'details' => 'Staff update',
                ],
                'record' => $case,
            ],
            [
                'show' => route(
                    'petcare.consultations.show',
                    $consultation,
                ),
                'update' => route(
                    'petcare.consultations.update',
                    $consultation,
                ),
                'payload' => [
                    'status' => 'in_progress',
                    'notes' => 'Staff update',
                ],
                'record' => $consultation,
            ],
        ];

        foreach ($workflows as $workflow) {
            $this->actingAs($revoked)
                ->get($workflow['show'])
                ->assertForbidden()
                ->assertDontSee($assignee->name);
            $this->actingAs($revoked)
                ->put($workflow['update'], [
                    ...$workflow['payload'],
                    'assigned_to' => $assignee->id,
                ])
                ->assertForbidden();
            $this->assertSame(
                $assignee->id,
                $workflow['record']->fresh()->assigned_to,
            );

            $this->actingAs($actor)
                ->put($workflow['update'], $workflow['payload'])
                ->assertRedirect();
        }

        Notification::assertNotSentTo(
            $revoked,
            TicketUpdatedNotification::class,
        );
        Notification::assertNotSentTo(
            $revoked,
            ShelterCaseUpdatedNotification::class,
        );
        Notification::assertNotSentTo(
            $revoked,
            ConsultationUpdatedNotification::class,
        );
        Notification::assertSentTo(
            $assignee,
            TicketUpdatedNotification::class,
        );
        Notification::assertSentTo(
            $assignee,
            ShelterCaseUpdatedNotification::class,
        );
        Notification::assertSentTo(
            $assignee,
            ConsultationUpdatedNotification::class,
        );
    }

    /**
     * @return array{SupportTicket, ShelterCase, PetCareConsultation}
     */
    private function records(User $owner, User $assignee): array
    {
        $shelterPet = PetProfile::query()->create([
            'user_id' => $owner->id,
            'service_domain' => PetProfile::DOMAIN_SHELTER,
            'name' => 'Shelter animal',
            'species' => 'dog',
        ]);
        $carePet = PetProfile::query()->create([
            'user_id' => $owner->id,
            'service_domain' => PetProfile::DOMAIN_PETCARE,
            'name' => 'Care animal',
            'species' => 'cat',
        ]);

        return [
            SupportTicket::query()->create([
                'user_id' => $owner->id,
                'assigned_to' => $assignee->id,
                'service_area' => 'it',
                'subject' => 'Security test ticket',
                'priority' => 'medium',
                'status' => 'open',
                'description' => 'Assignment integrity.',
            ]),
            ShelterCase::query()->create([
                'pet_profile_id' => $shelterPet->id,
                'user_id' => $owner->id,
                'assigned_to' => $assignee->id,
                'case_type' => 'rescue',
                'status' => 'open',
                'title' => 'Security test case',
                'details' => 'Assignment integrity.',
            ]),
            PetCareConsultation::query()->create([
                'pet_profile_id' => $carePet->id,
                'user_id' => $owner->id,
                'assigned_to' => $assignee->id,
                'subject' => 'Security test consultation',
                'status' => 'open',
                'notes' => 'Assignment integrity.',
            ]),
        ];
    }

    private function permissionOnlyUser(): User
    {
        $user = $this->user(User::ROLE_SERVICE_USER);
        $role = Role::query()->create([
            'name' => 'permission-only-'.uniqid(),
            'guard_name' => 'web',
        ]);
        $permission = Permission::query()
            ->where('name', AuthorizationProfile::PERMISSION_STAFF_ACCESS)
            ->where('guard_name', 'web')
            ->firstOrFail();
        $role->permissions()->attach($permission->id);
        app(AuthorizationRoleMaterializer::class)->grant(
            $user,
            $role,
            RoleSource::SOURCE_LOCAL,
            actor: $user,
        );

        return $user->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function user(string $accessLevel, array $attributes = []): User
    {
        return User::factory()
            ->accessLevel($accessLevel)
            ->create($attributes)
            ->refresh();
    }
}
