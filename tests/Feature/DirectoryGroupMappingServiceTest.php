<?php

namespace Tests\Feature;

use App\Exceptions\AuthorizationMutationDenied;
use App\Models\AuditLog;
use App\Models\DirectoryGroup;
use App\Models\DirectoryGroupRoleMapping;
use App\Models\Role;
use App\Models\RoleSource;
use App\Models\User;
use App\Services\AuthorizationMutationService;
use App\Services\AuthorizationProfile;
use App\Services\DirectoryGroupMappingService;
use App\Services\DirectoryRoleSynchronizer;
use App\Services\SessionAuthorizationContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DirectoryGroupMappingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_add_exact_protected_and_custom_role_mappings(): void
    {
        $actor = User::factory()
            ->accessLevel(User::ROLE_SUPERADMIN)
            ->create();
        $this->actingAs($actor);
        $group = DirectoryGroup::query()->create([
            'name' => 'myapes.case-reviewers',
            'status' => DirectoryGroup::STATUS_PRESENT,
        ]);
        $custom = Role::query()->create([
            'name' => 'case-reviewer',
            'guard_name' => 'web',
        ]);
        $administrator = Role::query()
            ->where('name', 'administrator')
            ->firstOrFail();
        $service = app(DirectoryGroupMappingService::class);

        $customMapping = $service->map($actor, $group, $custom);
        $protectedMapping = $service->map($actor, $group, $administrator);
        $repeated = $service->map($actor, $group, $custom);

        $this->assertFalse($customMapping->is_immutable);
        $this->assertFalse($protectedMapping->is_immutable);
        $this->assertTrue($customMapping->is($repeated));
        $this->assertDatabaseHas('directory_group_role_mappings', [
            'directory_group_id' => $group->id,
            'role_id' => $custom->id,
            'is_immutable' => false,
        ]);
        $this->assertDatabaseHas('directory_group_role_mappings', [
            'directory_group_id' => $group->id,
            'role_id' => $administrator->id,
            'is_immutable' => false,
        ]);
    }

    public function test_non_super_admin_cannot_change_mappings(): void
    {
        $actor = User::factory()->accessLevel(User::ROLE_ADMIN)->create();
        $this->actingAs($actor);
        $group = DirectoryGroup::query()
            ->where('name', 'myapes.staff')
            ->firstOrFail();
        $role = Role::query()->where('name', 'staff')->firstOrFail();

        $this->expectException(AuthorizationException::class);

        app(DirectoryGroupMappingService::class)->map($actor, $group, $role);
    }

    public function test_map_reloads_and_authorizes_the_locked_actor(): void
    {
        $actor = User::factory()
            ->accessLevel(User::ROLE_SUPERADMIN)
            ->create();
        $this->actingAs($actor);
        $group = DirectoryGroup::query()->create([
            'name' => 'myapes.case-reviewers',
            'status' => DirectoryGroup::STATUS_PRESENT,
        ]);
        $role = Role::query()->create([
            'name' => 'case-reviewer',
            'guard_name' => 'web',
        ]);
        DB::table('users')
            ->where('id', $actor->id)
            ->update(['suspended_at' => now()]);

        try {
            app(DirectoryGroupMappingService::class)
                ->map($actor, $group, $role);
            $this->fail('A stale suspended actor created a mapping.');
        } catch (AuthorizationException) {
            $this->assertDatabaseMissing('directory_group_role_mappings', [
                'directory_group_id' => $group->id,
                'role_id' => $role->id,
            ]);
        }
    }

    public function test_remove_reloads_and_authorizes_the_locked_actor(): void
    {
        $actor = User::factory()
            ->accessLevel(User::ROLE_SUPERADMIN)
            ->create();
        $this->actingAs($actor);
        $group = DirectoryGroup::query()->create([
            'name' => 'myapes.case-reviewers',
            'status' => DirectoryGroup::STATUS_PRESENT,
        ]);
        $role = Role::query()->create([
            'name' => 'case-reviewer',
            'guard_name' => 'web',
        ]);
        $service = app(DirectoryGroupMappingService::class);
        $mapping = $service->map($actor, $group, $role);
        DB::table('users')
            ->where('id', $actor->id)
            ->update(['suspended_at' => now()]);

        try {
            $service->remove($actor, $mapping);
            $this->fail('A stale suspended actor removed a mapping.');
        } catch (AuthorizationException) {
            $this->assertDatabaseHas('directory_group_role_mappings', [
                'id' => $mapping->id,
            ]);
        }
    }

    public function test_invalid_exact_groups_are_reason_coded_and_audited_after_rollback(): void
    {
        $actor = User::factory()
            ->accessLevel(User::ROLE_SUPERADMIN)
            ->create();
        $this->actingAs($actor);
        $role = Role::query()->where('name', 'staff')->firstOrFail();
        $service = app(DirectoryGroupMappingService::class);

        foreach (['myapes.*', 'position.staff'] as $name) {
            $group = DirectoryGroup::query()->create([
                'name' => $name,
                'status' => DirectoryGroup::STATUS_PRESENT,
            ]);

            try {
                $service->map($actor, $group, $role);
                $this->fail("The mapping service accepted [{$name}].");
            } catch (AuthorizationMutationDenied $exception) {
                $this->assertSame(
                    'invalid_directory_group',
                    $exception->reasonCode,
                );
                $this->assertSame(
                    'Directory group is not eligible for exact mapping.',
                    $exception->getMessage(),
                );
            }

            $audit = AuditLog::query()
                ->where(
                    'event',
                    'authorization.privileged_mutation_denied',
                )
                ->latest('id')
                ->firstOrFail();
            $this->assertSame($actor->id, $audit->user_id);
            $this->assertEquals([
                'action' => 'mapping_create',
                'reason_code' => 'invalid_directory_group',
                'group_id' => $group->id,
                'role_id' => $role->id,
            ], $audit->context);
        }

        $this->assertDatabaseMissing('directory_group_role_mappings', [
            'directory_group_id' => DirectoryGroup::query()
                ->where('name', 'myapes.*')
                ->value('id'),
        ]);
        $this->assertDatabaseMissing('directory_group_role_mappings', [
            'directory_group_id' => DirectoryGroup::query()
                ->where('name', 'position.staff')
                ->value('id'),
        ]);
    }

    public function test_immutable_mapping_removal_is_reason_coded_and_audited_after_rollback(): void
    {
        $actor = User::factory()
            ->accessLevel(User::ROLE_SUPERADMIN)
            ->create();
        $this->actingAs($actor);
        $mapping = DirectoryGroupRoleMapping::query()
            ->where('is_immutable', true)
            ->firstOrFail();

        try {
            app(DirectoryGroupMappingService::class)
                ->remove($actor, $mapping);
            $this->fail('An immutable directory mapping was removed.');
        } catch (AuthorizationMutationDenied $exception) {
            $this->assertSame('immutable_mapping', $exception->reasonCode);
            $this->assertSame(
                'Immutable directory mappings cannot be removed.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseHas('directory_group_role_mappings', [
            'id' => $mapping->id,
            'is_immutable' => true,
        ]);
        $audit = AuditLog::query()
            ->where('event', 'authorization.privileged_mutation_denied')
            ->sole();
        $this->assertSame($actor->id, $audit->user_id);
        $this->assertEquals([
            'action' => 'mapping_remove',
            'reason_code' => 'immutable_mapping',
            'group_id' => $mapping->directory_group_id,
            'role_id' => $mapping->role_id,
            'mapping_id' => $mapping->id,
        ], $audit->context);
    }

    public function test_removing_the_acting_final_super_admin_mapping_rolls_back(): void
    {
        $initialSuperAdmin = User::factory()
            ->accessLevel(User::ROLE_SUPERADMIN)
            ->create()
            ->refresh();
        $actor = User::factory()
            ->accessLevel(User::ROLE_STAFF)
            ->cloudronIdentity('delegated-super-admin')
            ->create()
            ->refresh();
        $group = DirectoryGroup::query()->create([
            'name' => 'myapes.delegated-superadmins',
            'status' => DirectoryGroup::STATUS_PRESENT,
            'member_count' => 1,
        ]);
        app(DirectoryRoleSynchronizer::class)->synchronize($actor, [
            'myapes.staff',
            $group->name,
        ]);
        $superAdminRole = Role::query()
            ->where('name', 'super-admin')
            ->firstOrFail();
        $service = app(DirectoryGroupMappingService::class);
        $this->actingAs($initialSuperAdmin);
        $mapping = $service->map(
            $initialSuperAdmin,
            $group,
            $superAdminRole,
        );
        $this->actingAs($actor->fresh());
        app(AuthorizationMutationService::class)->suspend(
            $initialSuperAdmin,
            $actor->fresh(),
            'Final-super-admin mapping regression fixture.',
        );

        try {
            $service->remove($actor->fresh(), $mapping);
            $this->fail('The final active super-admin mapping was removed.');
        } catch (AuthorizationMutationDenied $exception) {
            $this->assertSame(
                'final_active_super_admin',
                $exception->reasonCode,
            );
        }

        $this->assertDatabaseHas('directory_group_role_mappings', [
            'id' => $mapping->id,
        ]);
        $this->assertSame(
            'super-admin',
            app(AuthorizationProfile::class)
                ->effectiveProtectedRole($actor->fresh()),
        );
        $audit = AuditLog::query()
            ->where('event', 'authorization.privileged_mutation_denied')
            ->latest('id')
            ->firstOrFail();
        $this->assertEquals([
            'action' => 'mapping_remove',
            'reason_code' => 'final_active_super_admin',
            'group_id' => $group->id,
            'role_id' => $superAdminRole->id,
            'mapping_id' => $mapping->id,
        ], $audit->context);
    }

    public function test_creating_a_mapping_cannot_demote_the_final_super_admin(): void
    {
        $group = DirectoryGroup::query()->create([
            'name' => 'myapes.case-reviewers',
            'status' => DirectoryGroup::STATUS_PRESENT,
            'member_count' => 1,
        ]);
        $actor = User::factory()
            ->accessLevel(User::ROLE_SUPERADMIN)
            ->cloudronIdentity('transitional-super-admin')
            ->create(['ldap_groups' => [$group->name]])
            ->refresh();
        $staffRole = Role::query()
            ->where('name', 'staff')
            ->firstOrFail();
        $before = $this->authorizationSnapshot($actor);
        $this->actingAs($actor);

        try {
            app(DirectoryGroupMappingService::class)
                ->map($actor, $group, $staffRole);
            $this->fail('Mapping creation demoted the final super-admin.');
        } catch (AuthorizationMutationDenied $exception) {
            $this->assertSame(
                'final_active_super_admin',
                $exception->reasonCode,
            );
        }

        $this->assertDatabaseMissing('directory_group_role_mappings', [
            'directory_group_id' => $group->id,
            'role_id' => $staffRole->id,
        ]);
        $this->assertSame($before, $this->authorizationSnapshot($actor));
        $this->assertDatabaseMissing('audit_logs', [
            'event' => 'authorization.directory_mapping_changed',
            'user_id' => $actor->id,
        ]);
        $audit = AuditLog::query()
            ->where('event', 'authorization.privileged_mutation_denied')
            ->sole();
        $this->assertSame($actor->id, $audit->user_id);
        $this->assertEquals([
            'action' => 'mapping_create',
            'reason_code' => 'final_active_super_admin',
            'group_id' => $group->id,
            'role_id' => $staffRole->id,
        ], $audit->context);
    }

    public function test_existing_mapping_resynchronization_cannot_demote_the_final_super_admin(): void
    {
        $initialSuperAdmin = User::factory()
            ->accessLevel(User::ROLE_SUPERADMIN)
            ->create()
            ->refresh();
        $group = DirectoryGroup::query()->create([
            'name' => 'myapes.case-reviewers',
            'status' => DirectoryGroup::STATUS_PRESENT,
            'member_count' => 1,
        ]);
        $staffRole = Role::query()
            ->where('name', 'staff')
            ->firstOrFail();
        $service = app(DirectoryGroupMappingService::class);
        $this->actingAs($initialSuperAdmin);
        $mapping = $service->map(
            $initialSuperAdmin,
            $group,
            $staffRole,
        );
        $actor = User::factory()
            ->accessLevel(User::ROLE_SUPERADMIN)
            ->cloudronIdentity('existing-mapping-super-admin')
            ->create(['ldap_groups' => [$group->name]])
            ->refresh();
        $this->actingAs($actor);
        app(AuthorizationMutationService::class)->suspend(
            $initialSuperAdmin,
            $actor,
            'Existing mapping final-super-admin fixture.',
        );
        $before = $this->authorizationSnapshot($actor);
        $changeAuditCount = AuditLog::query()
            ->where('event', 'authorization.directory_mapping_changed')
            ->count();

        try {
            $service->map($actor->fresh(), $group, $staffRole);
            $this->fail('Existing mapping sync demoted the final super-admin.');
        } catch (AuthorizationMutationDenied $exception) {
            $this->assertSame(
                'final_active_super_admin',
                $exception->reasonCode,
            );
        }

        $this->assertDatabaseHas('directory_group_role_mappings', [
            'id' => $mapping->id,
        ]);
        $this->assertSame($before, $this->authorizationSnapshot($actor));
        $this->assertSame(
            $changeAuditCount,
            AuditLog::query()
                ->where('event', 'authorization.directory_mapping_changed')
                ->count(),
        );
        $audit = AuditLog::query()
            ->where('event', 'authorization.privileged_mutation_denied')
            ->latest('id')
            ->firstOrFail();
        $this->assertEquals([
            'action' => 'mapping_create',
            'reason_code' => 'final_active_super_admin',
            'group_id' => $group->id,
            'role_id' => $staffRole->id,
        ], $audit->context);
    }

    public function test_missing_group_backed_super_admin_pivot_cannot_satisfy_the_final_invariant(): void
    {
        $bootstrap = User::factory()
            ->accessLevel(User::ROLE_SUPERADMIN)
            ->create()
            ->refresh();
        $this->authenticateQaContext($bootstrap);
        $superAdminRole = Role::query()
            ->where('name', AuthorizationProfile::ROLE_SUPER_ADMIN)
            ->firstOrFail();
        $primaryGroup = DirectoryGroup::query()->create([
            'name' => 'myapes.primary-superadmins',
            'status' => DirectoryGroup::STATUS_PRESENT,
            'member_count' => 1,
        ]);
        $staleGroup = DirectoryGroup::query()->create([
            'name' => 'myapes.stale-superadmins',
            'status' => DirectoryGroup::STATUS_PRESENT,
            'member_count' => 1,
        ]);
        $service = app(DirectoryGroupMappingService::class);
        $primaryMapping = $service->map(
            $bootstrap,
            $primaryGroup,
            $superAdminRole,
        );
        $service->map($bootstrap, $staleGroup, $superAdminRole);
        $actor = User::factory()
            ->cloudronIdentity('primary-super-admin')
            ->create(['ldap_groups' => [$primaryGroup->name]])
            ->refresh();
        $stale = User::factory()
            ->cloudronIdentity('stale-super-admin')
            ->create(['ldap_groups' => [$staleGroup->name]])
            ->refresh();
        $synchronizer = app(DirectoryRoleSynchronizer::class);
        $synchronizer->synchronize($actor, [$primaryGroup->name]);
        $synchronizer->synchronize($stale, [$staleGroup->name]);
        RoleSource::query()
            ->whereIn('user_id', [$actor->id, $stale->id])
            ->where('role_id', $superAdminRole->id)
            ->where('source', '<>', RoleSource::SOURCE_DIRECTORY)
            ->delete();
        $bootstrap->forceFill(['suspended_at' => now()])->save();
        $staleGroup->forceFill([
            'status' => DirectoryGroup::STATUS_MISSING,
            'member_count' => 0,
        ])->save();
        $this->assertTrue(
            app(AuthorizationProfile::class)->isSuperAdmin($stale->fresh()),
            'The regression fixture must retain the stale raw role pivot.',
        );
        $this->authenticateQaContext($actor->fresh());

        try {
            $service->remove($actor->fresh(), $primaryMapping);
            $this->fail('A missing-group pivot satisfied the final super-admin invariant.');
        } catch (AuthorizationMutationDenied $exception) {
            $this->assertSame(
                'final_active_super_admin',
                $exception->reasonCode,
            );
        }

        $this->assertDatabaseHas('directory_group_role_mappings', [
            'id' => $primaryMapping->id,
        ]);
        $this->assertSame(
            AuthorizationProfile::ROLE_SUPER_ADMIN,
            app(AuthorizationProfile::class)
                ->effectiveProtectedRole($actor->fresh()),
        );
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

    /**
     * @return array{
     *     legacy_access_level: mixed,
     *     authorization_epoch: int,
     *     remember_token: mixed,
     *     role_ids: array<int, int>,
     *     role_sources: array<int, array<string, mixed>>
     * }
     */
    private function authorizationSnapshot(User $user): array
    {
        $stored = $user->fresh();

        return [
            'legacy_access_level' => $stored->getAttribute(
                'legacy_access_level',
            ),
            'authorization_epoch' => (int) $stored->authorization_epoch,
            'remember_token' => $stored->getRememberToken(),
            'role_ids' => DB::table(
                config('permission.table_names.model_has_roles'),
            )
                ->where('model_type', User::class)
                ->where('model_id', $user->id)
                ->orderBy('role_id')
                ->pluck('role_id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all(),
            'role_sources' => DB::table('role_sources')
                ->where('user_id', $user->id)
                ->orderBy('id')
                ->get([
                    'role_id',
                    'source_key',
                    'source',
                    'directory_group_id',
                    'granted_by',
                ])
                ->map(static fn (object $source): array => (array) $source)
                ->all(),
        ];
    }

    public function test_super_admin_can_enable_and_disable_non_required_groups(): void
    {
        $actor = User::factory()
            ->accessLevel(User::ROLE_SUPERADMIN)
            ->create();
        $this->actingAs($actor);
        $group = DirectoryGroup::query()->create([
            'name' => 'ops.volunteers',
            'status' => DirectoryGroup::STATUS_PRESENT,
            'app_enabled' => true,
        ]);
        $service = app(DirectoryGroupMappingService::class);

        $disabled = $service->setAppEnabled($actor, $group, false);
        $this->assertFalse($disabled->app_enabled);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'authorization.directory_group_app_access_changed',
        ]);

        $enabled = $service->setAppEnabled($actor, $group->fresh(), true);
        $this->assertTrue($enabled->app_enabled);
    }

    public function test_required_cloudron_groups_cannot_be_disabled(): void
    {
        $actor = User::factory()
            ->accessLevel(User::ROLE_SUPERADMIN)
            ->create();
        $this->actingAs($actor);
        $group = DirectoryGroup::query()
            ->where('name', 'myapes.staff')
            ->firstOrFail();

        $this->expectException(AuthorizationMutationDenied::class);
        $this->expectExceptionMessage(
            'Required Cloudron MyAPES groups cannot be disabled for this app.',
        );

        app(DirectoryGroupMappingService::class)
            ->setAppEnabled($actor, $group, false);
    }
}
