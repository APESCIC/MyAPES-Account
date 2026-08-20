<?php

namespace Tests\Feature;

use App\Exceptions\AuthorizationMutationDenied;
use App\Models\AuditLog;
use App\Models\DirectoryGroup;
use App\Models\DirectoryGroupRoleMapping;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleSource;
use App\Models\User;
use App\Services\AuthorizationMutationService;
use App\Services\AuthorizationProfile;
use App\Services\AuthorizationRoleManagementService;
use App\Services\AuthorizationRoleMaterializer;
use App\Services\DirectoryRoleSynchronizer;
use App\Services\SessionAuthorizationContext;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminRoleAndUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_manages_custom_roles_and_suspension_only_for_service_and_staff_targets(): void
    {
        $administrator = $this->userWithAccess(User::ROLE_ADMIN);
        $serviceUser = $this->userWithAccess(User::ROLE_SERVICE_USER);
        $staff = $this->userWithAccess(User::ROLE_STAFF);
        $otherAdministrator = $this->userWithAccess(User::ROLE_ADMIN);
        $customRole = Role::query()->create([
            'name' => 'case-reviewer',
            'guard_name' => 'web',
        ]);

        $this->actingAs($administrator)
            ->put("/admin/users/{$serviceUser->id}/roles", [
                'roles' => [$customRole->id],
            ])
            ->assertRedirect("/admin/users/{$serviceUser->id}");
        $this->assertDatabaseHas('role_sources', [
            'user_id' => $serviceUser->id,
            'role_id' => $customRole->id,
            'source' => RoleSource::SOURCE_LOCAL,
            'granted_by' => $administrator->id,
        ]);

        $this->actingAs($administrator)
            ->post("/admin/users/{$staff->id}/suspension", [
                'reason' => 'Temporary access review',
            ])
            ->assertRedirect("/admin/users/{$staff->id}");
        $this->assertNotNull($staff->fresh()->suspended_at);

        $this->actingAs($administrator)
            ->delete("/admin/users/{$staff->id}/suspension")
            ->assertRedirect("/admin/users/{$staff->id}");
        $this->assertNull($staff->fresh()->suspended_at);

        $this->actingAs($administrator)
            ->from("/admin/users/{$otherAdministrator->id}")
            ->post("/admin/users/{$otherAdministrator->id}/suspension", [
                'reason' => 'Not permitted',
            ])
            ->assertRedirect("/admin/users/{$otherAdministrator->id}")
            ->assertSessionHasErrors('authorization');
        $this->assertNull($otherAdministrator->fresh()->suspended_at);

        $denial = AuditLog::query()
            ->where('event', 'authorization.privileged_mutation_denied')
            ->where('user_id', $administrator->id)
            ->latest('id')
            ->firstOrFail();
        $this->assertSame('suspend', $denial->context['action']);
        $this->assertSame(
            'administrator_target_requires_super_admin',
            $denial->context['reason_code'],
        );
        $this->assertSame(
            $otherAdministrator->id,
            $denial->context['target_user_id'],
        );
    }

    public function test_self_changes_and_protected_local_assignments_are_rejected_at_service_boundaries(): void
    {
        $superAdmin = $this->userWithAccess(User::ROLE_SUPERADMIN);
        $protectedRole = Role::query()
            ->where('name', 'administrator')
            ->firstOrFail();
        $service = app(AuthorizationMutationService::class);
        $this->actingAs($superAdmin);

        foreach ([
            fn () => $service->suspend($superAdmin, $superAdmin, 'Self'),
            fn () => $service->grantLocalRole(
                $this->userWithAccess(User::ROLE_SERVICE_USER),
                $protectedRole,
                $superAdmin,
            ),
        ] as $mutation) {
            try {
                $mutation();
                $this->fail('A forbidden authorization mutation succeeded.');
            } catch (DomainException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertDatabaseMissing('role_sources', [
            'role_id' => $protectedRole->id,
            'source' => RoleSource::SOURCE_LOCAL,
        ]);
        $this->assertSame(
            2,
            AuditLog::query()
                ->where('event', 'authorization.privileged_mutation_denied')
                ->count(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Protected roles cannot have local provenance.',
        );
        app(AuthorizationRoleMaterializer::class)->grant(
            $this->userWithAccess(User::ROLE_SERVICE_USER),
            $protectedRole,
            RoleSource::SOURCE_LOCAL,
        );
    }

    public function test_custom_role_lifecycle_validates_catalogue_resets_cache_and_invalidates_assigned_users(): void
    {
        $superAdmin = $this->userWithAccess(User::ROLE_SUPERADMIN);
        $target = $this->userWithAccess(User::ROLE_SERVICE_USER);

        $this->actingAs($superAdmin)
            ->post('/admin/roles', [
                'name' => 'case-reviewer',
                'permissions' => ['admin.users.view'],
            ])
            ->assertRedirect();
        $role = Role::query()->where('name', 'case-reviewer')->sole();
        $this->assertFalse($role->is_protected);
        $this->assertSame(
            ['admin.users.view'],
            $role->permissions()->pluck('name')->all(),
        );
        $this->actingAs($superAdmin)
            ->from('/admin/roles')
            ->post('/admin/roles', [
                'name' => 'case-reviewer',
                'permissions' => [],
            ])
            ->assertRedirect('/admin/roles')
            ->assertSessionHasErrors('name');

        foreach ([
            'Bad Role',
            'staff',
            'ab',
        ] as $invalidName) {
            $this->actingAs($superAdmin)
                ->from('/admin/roles')
                ->post('/admin/roles', [
                    'name' => $invalidName,
                    'permissions' => [],
                ])
                ->assertRedirect('/admin/roles')
                ->assertSessionHasErrors('name');
        }
        $this->actingAs($superAdmin)
            ->from('/admin/roles')
            ->post('/admin/roles', [
                'name' => 'unknown-permission-role',
                'permissions' => ['not.in.catalogue'],
            ])
            ->assertRedirect('/admin/roles')
            ->assertSessionHasErrors('permissions.0');

        app(AuthorizationRoleMaterializer::class)->grant(
            $target,
            $role,
            RoleSource::SOURCE_LOCAL,
            actor: $superAdmin,
        );
        $target->forceFill(['remember_token' => 'before-role-permissions'])
            ->save();
        $epoch = $target->authorization_epoch;
        Cache::put(config('permission.cache.key'), 'stale');

        $this->actingAs($superAdmin)
            ->put("/admin/roles/{$role->id}", [
                'name' => 'case-reviewer',
                'permissions' => [
                    'admin.users.view',
                    'admin.users.manage',
                ],
            ])
            ->assertRedirect("/admin/roles/{$role->id}");

        $target->refresh();
        $this->assertSame($epoch + 1, $target->authorization_epoch);
        $this->assertNotSame(
            'before-role-permissions',
            $target->getRememberToken(),
        );
        $this->assertFalse(Cache::has(config('permission.cache.key')));
        $this->assertSame(
            ['admin.users.manage', 'admin.users.view'],
            $role->fresh()->permissions()->orderBy('name')->pluck('name')->all(),
        );

        $this->actingAs($superAdmin)
            ->from("/admin/roles/{$role->id}")
            ->delete("/admin/roles/{$role->id}")
            ->assertRedirect("/admin/roles/{$role->id}")
            ->assertSessionHasErrors('authorization');
        $this->assertDatabaseHas('roles', ['id' => $role->id]);

        app(AuthorizationRoleMaterializer::class)->revoke(
            $target,
            $role,
            RoleSource::SOURCE_LOCAL,
        );
        $this->actingAs($superAdmin)
            ->delete("/admin/roles/{$role->id}")
            ->assertRedirect('/admin/roles');
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'authorization.role_deleted',
            'user_id' => $superAdmin->id,
        ]);
    }

    public function test_protected_roles_are_read_only_even_for_super_admins(): void
    {
        $superAdmin = $this->userWithAccess(User::ROLE_SUPERADMIN);
        $protected = Role::query()->where('name', 'staff')->firstOrFail();
        $permission = Permission::query()
            ->where('name', 'admin.users.view')
            ->firstOrFail();

        $this->actingAs($superAdmin)
            ->from("/admin/roles/{$protected->id}")
            ->put("/admin/roles/{$protected->id}", [
                'name' => 'renamed-staff',
                'permissions' => [$permission->name],
            ])
            ->assertRedirect("/admin/roles/{$protected->id}")
            ->assertSessionHasErrors('authorization');
        $this->actingAs($superAdmin)
            ->from("/admin/roles/{$protected->id}")
            ->delete("/admin/roles/{$protected->id}")
            ->assertRedirect("/admin/roles/{$protected->id}")
            ->assertSessionHasErrors('authorization');

        $this->assertDatabaseHas('roles', [
            'id' => $protected->id,
            'name' => 'staff',
            'is_protected' => true,
        ]);
    }

    public function test_custom_role_deletion_rejects_mappings_and_unsafe_permissions(): void
    {
        $superAdmin = $this->userWithAccess(User::ROLE_SUPERADMIN);
        $role = Role::query()->create([
            'name' => 'mapped-reviewer',
            'guard_name' => 'web',
        ]);
        $group = DirectoryGroup::query()->create([
            'name' => 'myapes.mapped-reviewer',
            'status' => DirectoryGroup::STATUS_PRESENT,
        ]);
        $mapping = new DirectoryGroupRoleMapping;
        $mapping->forceFill([
            'directory_group_id' => $group->id,
            'role_id' => $role->id,
            'is_immutable' => false,
        ])->save();

        $this->actingAs($superAdmin)
            ->from("/admin/roles/{$role->id}")
            ->delete("/admin/roles/{$role->id}")
            ->assertRedirect("/admin/roles/{$role->id}")
            ->assertSessionHasErrors('authorization');
        $this->assertDatabaseHas('roles', ['id' => $role->id]);

        $mapping->delete();
        $unsafePermission = Permission::query()->create([
            'name' => 'unsafe.direct',
            'guard_name' => 'web',
        ]);
        $role->permissions()->attach($unsafePermission);

        $this->actingAs($superAdmin)
            ->from("/admin/roles/{$role->id}")
            ->delete("/admin/roles/{$role->id}")
            ->assertRedirect("/admin/roles/{$role->id}")
            ->assertSessionHasErrors('authorization');
        $this->assertDatabaseHas('roles', ['id' => $role->id]);

        $reasonCodes = AuditLog::query()
            ->where('event', 'authorization.privileged_mutation_denied')
            ->pluck('context')
            ->map(static fn (array $context): string => $context['reason_code'])
            ->all();
        $this->assertSame(
            ['role_is_assigned', 'role_has_unsafe_permissions'],
            $reasonCodes,
        );
    }

    public function test_role_management_revalidates_directory_eligibility_inside_the_transaction(): void
    {
        $group = DirectoryGroup::query()
            ->where('name', 'myapes.superadmin')
            ->firstOrFail();
        $group->forceFill([
            'status' => DirectoryGroup::STATUS_PRESENT,
            'member_count' => 1,
        ])->save();
        $actor = User::factory()
            ->cloudronIdentity('transaction-fence-super-admin')
            ->create(['ldap_groups' => [$group->name]])
            ->refresh();
        app(DirectoryRoleSynchronizer::class)->synchronize(
            $actor,
            [$group->name],
        );
        $actor->refresh();
        $superAdminRoleId = Role::query()
            ->where('name', AuthorizationProfile::ROLE_SUPER_ADMIN)
            ->value('id');
        RoleSource::query()
            ->whereBelongsTo($actor)
            ->where('role_id', $superAdminRoleId)
            ->where('source', '<>', RoleSource::SOURCE_DIRECTORY)
            ->delete();
        $this->assertSame(
            AuthorizationProfile::ROLE_SUPER_ADMIN,
            app(AuthorizationProfile::class)->effectiveProtectedRole($actor),
        );
        $this->authenticateQaContext($actor);

        // This models a successful outer Gate followed by directory revocation
        // before the service acquires its transaction locks.
        $group->forceFill([
            'status' => DirectoryGroup::STATUS_MISSING,
            'member_count' => 0,
        ])->save();

        try {
            app(AuthorizationRoleManagementService::class)->create(
                $actor,
                'stale-directory-manager',
                [],
            );
            $this->fail('A directory-revoked actor created a role.');
        } catch (AuthorizationMutationDenied $exception) {
            $this->assertSame('super_admin_required', $exception->reasonCode);
        }

        $this->assertDatabaseMissing('roles', [
            'name' => 'stale-directory-manager',
            'guard_name' => 'web',
        ]);
    }

    private function authenticateQaContext(User $user): void
    {
        $this->actingAs($user);

        if (! request()->hasSession()) {
            request()->setLaravelSession(app('session')->driver());
        }

        request()->session()->put(
            app(SessionAuthorizationContext::class)->valuesFor(
                $user->fresh(),
                SessionAuthorizationContext::METHOD_QA,
            ),
        );
    }

    private function userWithAccess(string $accessLevel): User
    {
        return User::factory()
            ->accessLevel($accessLevel)
            ->create()
            ->refresh();
    }
}
