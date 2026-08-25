<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DirectoryGroup;
use App\Models\Permission;
use App\Models\PermissionSource;
use App\Models\Role;
use App\Models\RoleSource;
use App\Models\User;
use App\Services\AuthorizationDirectPermissionMaterializer;
use App\Services\AuthorizationProfile;
use App\Services\AuthorizationRoleMaterializer;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessAndViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_routes_enforce_exact_permissions_and_audit_denials(): void
    {
        $administrator = $this->userWithAccess(User::ROLE_ADMIN);

        $this->actingAs($administrator)->get('/admin/users')->assertOk();

        foreach ([
            '/admin/groups',
            '/admin/roles',
            '/admin/permissions',
            '/admin/modules',
            '/superadmin',
        ] as $path) {
            $this->actingAs($administrator)->get($path)->assertForbidden();
        }

        $this->actingAs($administrator)
            ->post('/admin/roles', [
                'name' => 'case-reviewer',
                'permissions' => [],
            ])
            ->assertForbidden();
        $this->actingAs($administrator)
            ->post('/admin/groups/sync')
            ->assertForbidden();

        $denials = AuditLog::query()
            ->where('event', 'authorization.admin_denied')
            ->where('user_id', $administrator->id)
            ->get();

        $this->assertGreaterThanOrEqual(2, $denials->count());
        $this->assertTrue(
            $denials->pluck('context.route_name')->contains('admin.roles.store'),
        );
        $this->assertTrue(
            $denials->pluck('context.route_name')->contains('admin.groups.sync'),
        );
        $this->assertSame(
            ['permission_denied'],
            $denials->pluck('context.reason_code')->unique()->values()->all(),
        );
    }

    public function test_staff_cannot_view_admin_user_management_and_the_denial_is_audited(): void
    {
        $staff = $this->userWithAccess(User::ROLE_STAFF);

        $this->actingAs($staff)->get('/admin/users')->assertForbidden();

        $denial = AuditLog::query()
            ->where('event', 'authorization.admin_denied')
            ->sole();
        $this->assertSame($staff->id, $denial->user_id);
        $this->assertSame($staff->id, $denial->context['actor_id']);
        $this->assertSame('admin.users.index', $denial->context['route_name']);
        $this->assertSame('GET', $denial->context['method']);
        $this->assertSame(
            'permission_denied',
            $denial->context['reason_code'],
        );
    }

    public function test_user_filters_and_detail_are_validated_semantic_and_sanitized(): void
    {
        $administrator = $this->userWithAccess(User::ROLE_ADMIN);
        $target = User::factory()
            ->accessLevel(User::ROLE_STAFF)
            ->cloudronIdentity('directory-subject-must-not-render')
            ->create([
                'name' => 'Needle Person',
                'email' => 'needle@example.test',
                'ldap_groups' => ['myapesaccount.staff', 'myapes.case-reviewers'],
            ])
            ->refresh();
        User::factory()->accessLevel(User::ROLE_SERVICE_USER)->create([
            'name' => 'Other Person',
            'email' => 'other@example.test',
        ]);
        $role = Role::query()->create([
            'name' => 'case-reviewer',
            'guard_name' => 'web',
        ]);
        app(AuthorizationRoleMaterializer::class)->grant(
            $target,
            $role,
            RoleSource::SOURCE_LOCAL,
            actor: $administrator,
        );
        AuditLog::query()->create([
            'user_id' => $administrator->id,
            'event' => 'authorization.test_history',
            'auditable_type' => User::class,
            'auditable_id' => $target->id,
            'context' => [
                'target_user_id' => $target->id,
                'reason_code' => 'safe_history_code',
                'oidc_sub' => 'unsafe-subject',
                'raw_payload' => ['credential' => 'unsafe-value'],
            ],
        ]);

        $this->actingAs($administrator)
            ->get('/admin/users?q=Needle&identity_type=cloudron_oidc&status=active&protected_role=staff')
            ->assertOk()
            ->assertSee('Needle Person')
            ->assertDontSee('Other Person')
            ->assertSee('<main', false)
            ->assertSee('<table', false)
            ->assertSee('<caption', false);

        $this->actingAs($administrator)
            ->get("/admin/users/{$target->id}")
            ->assertOk()
            ->assertSee('Needle Person')
            ->assertSee('needle@example.test')
            ->assertSee('Cloudron OIDC')
            ->assertSee('myapes.case-reviewers')
            ->assertSee('case-reviewer')
            ->assertSee('local')
            ->assertSee('staff.access')
            ->assertSee('safe_history_code')
            ->assertDontSee('directory-subject-must-not-render')
            ->assertDontSee('unsafe-subject')
            ->assertDontSee('unsafe-value')
            ->assertSee('<h1', false)
            ->assertSee('<section', false);

        $this->actingAs($administrator)
            ->from('/admin/users')
            ->get('/admin/users?status=deleted')
            ->assertRedirect('/admin/users')
            ->assertSessionHasErrors('status');
    }

    public function test_user_detail_matches_runtime_direct_permissions_and_renders_safe_provenance(): void
    {
        $grantingAdministrator = $this->userWithAccess(User::ROLE_ADMIN);
        $reviewingAdministrator = $this->userWithAccess(User::ROLE_ADMIN);
        $target = $this->userWithAccess(User::ROLE_STAFF);
        $materializer = app(AuthorizationDirectPermissionMaterializer::class);
        $localPermission = Permission::query()->create([
            'name' => 'admin.users.*',
            'guard_name' => 'web',
        ]);
        $systemPermission = Permission::query()
            ->where('name', 'admin.users.view')
            ->where('guard_name', 'web')
            ->firstOrFail();
        $duplicatePermission = Permission::query()
            ->where('name', 'staff.access')
            ->where('guard_name', 'web')
            ->firstOrFail();

        $materializer->grant(
            $target,
            $localPermission,
            PermissionSource::SOURCE_LOCAL,
            actor: $grantingAdministrator,
        );
        $materializer->grant(
            $target,
            $systemPermission,
            PermissionSource::SOURCE_SYSTEM,
        );
        $materializer->grant(
            $target,
            $duplicatePermission,
            PermissionSource::SOURCE_SYSTEM,
        );

        $this->actingAs($target)
            ->get(route('admin.users.index'))
            ->assertOk();

        $deniedStaff = $this->userWithAccess(User::ROLE_STAFF);
        $this->actingAs($deniedStaff)
            ->get(route('admin.users.show', $target))
            ->assertForbidden()
            ->assertDontSee('admin.users.*');

        $response = $this->actingAs($reviewingAdministrator)
            ->get(route('admin.users.show', $target))
            ->assertOk()
            ->assertSee('admin.users.*')
            ->assertSee('admin.users.view')
            ->assertSee('Direct permission provenance')
            ->assertSee('local')
            ->assertSee((string) $grantingAdministrator->id)
            ->assertSee('System');

        $this->assertSame(
            1,
            substr_count(
                $response->getContent(),
                '<li><code>staff.access</code></li>',
            ),
        );
    }

    public function test_group_role_and_permission_lists_support_validated_search_and_semantic_markup(): void
    {
        $superAdmin = $this->userWithAccess(User::ROLE_SUPERADMIN);
        DirectoryGroup::query()
            ->where('name', 'myapesaccount.volunteer')
            ->update([
                'status' => DirectoryGroup::STATUS_PRESENT,
                'member_count' => 0,
                'app_enabled' => true,
            ]);

        $this->actingAs($superAdmin)
            ->get('/admin/groups?q=volunteer&status=present')
            ->assertOk()
            ->assertSee('myapesaccount.volunteer')
            ->assertSee('Cloudron MyAPES Account')
            ->assertSee('Preset Cloudron mapping')
            ->assertSee('<table', false);
        $this->actingAs($superAdmin)
            ->get('/admin/roles?q=case')
            ->assertOk()
            ->assertSee('Protected roles sync from application code');
        $this->actingAs($superAdmin)
            ->get('/admin/permissions?q=users.view')
            ->assertOk()
            ->assertSee('admin.users.view')
            ->assertSee('View accounts')
            ->assertDontSee('admin.roles.manage')
            ->assertSee('Code-owned catalogue');
    }

    public function test_groups_ui_is_read_only_for_preset_cloudron_groups(): void
    {
        $superAdmin = $this->userWithAccess(User::ROLE_SUPERADMIN);
        $group = DirectoryGroup::query()
            ->where('name', 'myapesaccount.staff')
            ->firstOrFail();

        $this->actingAs($superAdmin)
            ->get('/admin/groups')
            ->assertOk()
            ->assertSee('myapesaccount.staff')
            ->assertSee('Preset Cloudron mapping')
            ->assertDontSee('Enable for this app')
            ->assertDontSee('Disable for this app')
            ->assertSee('Add job role mapping');

        $this->actingAs($superAdmin)
            ->post('/admin/groups/999999/enable')
            ->assertNotFound();
        $this->actingAs($superAdmin)
            ->post('/admin/groups/999999/disable')
            ->assertNotFound();
    }

    public function test_super_admin_can_read_custom_role_permissions_and_administrators_cannot(): void
    {
        $administrator = $this->userWithAccess(User::ROLE_ADMIN);
        $superAdmin = $this->userWithAccess(User::ROLE_SUPERADMIN);
        $role = Role::query()->create([
            'name' => 'case-reviewer',
            'guard_name' => 'web',
        ]);
        $permission = Permission::query()
            ->where('name', 'admin.users.view')
            ->where('guard_name', 'web')
            ->firstOrFail();
        $role->permissions()->attach($permission->id);

        $this->actingAs($administrator)
            ->get("/admin/roles/{$role->id}")
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->get("/admin/roles/{$role->id}")
            ->assertOk()
            ->assertSee('View accounts')
            ->assertSee('admin.users.view')
            ->assertSee('Update role');
    }

    public function test_admin_mutations_require_csrf_tokens(): void
    {
        $superAdmin = $this->userWithAccess(User::ROLE_SUPERADMIN);
        $this->app->instance('env', 'production');

        $this->withMiddleware(ValidateCsrfToken::class)
            ->actingAs($superAdmin)
            ->post('/admin/groups/sync')
            ->assertStatus(419);
    }

    public function test_admin_identifier_probes_are_denied_before_lookup_with_identical_audits(): void
    {
        $staff = $this->userWithAccess(User::ROLE_STAFF);
        $target = $this->userWithAccess(User::ROLE_SERVICE_USER);

        $pairs = [
            [
                $this->actingAs($staff)
                    ->get(route('admin.users.show', $target)),
                $this->actingAs($staff)
                    ->get(route('admin.users.show', 999999)),
            ],
            [
                $this->actingAs($staff)->put(
                    route('admin.users.roles.update', $target),
                    ['roles' => []],
                ),
                $this->actingAs($staff)->put(
                    route('admin.users.roles.update', 999999),
                    ['roles' => []],
                ),
            ],
            [
                $this->actingAs($staff)->post(
                    route('admin.users.suspension.store', $target),
                    ['reason' => 'Denied probe'],
                ),
                $this->actingAs($staff)->post(
                    route('admin.users.suspension.store', 999999),
                    ['reason' => 'Denied probe'],
                ),
            ],
            [
                $this->actingAs($staff)->delete(
                    route('admin.users.suspension.destroy', $target),
                ),
                $this->actingAs($staff)->delete(
                    route('admin.users.suspension.destroy', 999999),
                ),
            ],
        ];

        foreach ($pairs as [$existing, $missing]) {
            $existing->assertForbidden();
            $missing->assertForbidden();
            $this->assertSame(
                $existing->getContent(),
                $missing->getContent(),
            );
        }

        $audits = AuditLog::query()
            ->where('event', 'authorization.admin_denied')
            ->where('user_id', $staff->id)
            ->get();
        $this->assertCount(8, $audits);
        $this->assertEqualsCanonicalizing([
            'admin.users.show',
            'admin.users.roles.update',
            'admin.users.suspension.store',
            'admin.users.suspension.destroy',
        ], $audits->pluck('context.route_name')->unique()->values()->all());
        $this->assertSame(
            ['permission_denied'],
            $audits->pluck('context.reason_code')->unique()->values()->all(),
        );
        $this->assertStringNotContainsString(
            (string) $target->id,
            $audits->pluck('context')->toJson(),
        );
        $this->assertNull($target->fresh()->suspended_at);
    }

    public function test_admin_access_only_dashboard_keeps_aggregates_without_recent_identities(): void
    {
        $actor = $this->userWithAccess(User::ROLE_STAFF);
        $role = Role::query()->create([
            'name' => 'dashboard-operator',
            'guard_name' => 'web',
        ]);
        $permissions = Permission::query()
            ->whereIn('name', [
                AuthorizationProfile::PERMISSION_ADMIN_ACCESS,
                'admin.analytics.view',
            ])
            ->where('guard_name', 'web')
            ->pluck('id');
        $role->permissions()->attach($permissions);
        app(AuthorizationRoleMaterializer::class)->grant(
            $actor,
            $role,
            RoleSource::SOURCE_LOCAL,
            actor: $actor,
        );
        $recent = User::factory()->create([
            'name' => 'Identity must remain hidden',
            'email' => 'hidden@example.test',
        ]);

        $this->actingAs($actor)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Total accounts')
            ->assertDontSee($recent->name)
            ->assertDontSee($recent->email)
            ->assertDontSee('Recent accounts');
        $this->actingAs($actor)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $usersView = Permission::query()
            ->where(
                'name',
                'admin.users.view',
            )
            ->where('guard_name', 'web')
            ->firstOrFail();
        $role->permissions()->attach($usersView->id);
        $actor->unsetRelation('roles');

        $this->actingAs($actor)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Recent accounts')
            ->assertSee($recent->name)
            ->assertSee($recent->email);
    }

    public function test_role_and_group_identifier_probes_are_denied_before_lookup(): void
    {
        $administrator = $this->userWithAccess(User::ROLE_ADMIN);
        $staff = $this->userWithAccess(User::ROLE_STAFF);
        $role = Role::query()->create([
            'name' => 'oracle-test-role',
            'guard_name' => 'web',
        ]);

        $roleExisting = $this->actingAs($administrator)->put(
            route('admin.roles.update', $role),
            ['name' => 'ignored-role', 'permissions' => []],
        );
        $roleMissing = $this->actingAs($administrator)->put(
            route('admin.roles.update', 999999),
            ['name' => 'ignored-role', 'permissions' => []],
        );
        $groupSync = $this->actingAs($administrator)->post('/admin/groups/sync');
        $roleShowExisting = $this->actingAs($staff)->get(
            route('admin.roles.show', $role),
        );
        $roleShowMissing = $this->actingAs($staff)->get(
            route('admin.roles.show', 999999),
        );
        $roleDestroyExisting = $this->actingAs($staff)->delete(
            route('admin.roles.destroy', $role),
        );
        $roleDestroyMissing = $this->actingAs($staff)->delete(
            route('admin.roles.destroy', 999999),
        );

        foreach ([
            $roleExisting,
            $roleMissing,
            $groupSync,
            $roleShowExisting,
            $roleShowMissing,
            $roleDestroyExisting,
            $roleDestroyMissing,
        ] as $response) {
            $response->assertForbidden();
        }
        $this->assertSame(
            $roleExisting->getContent(),
            $roleMissing->getContent(),
        );
        $this->assertSame(
            $roleShowExisting->getContent(),
            $roleShowMissing->getContent(),
        );
        $this->assertSame(
            $roleDestroyExisting->getContent(),
            $roleDestroyMissing->getContent(),
        );

        $audits = AuditLog::query()
            ->where('event', 'authorization.admin_denied')
            ->get();
        $this->assertCount(7, $audits);
        $this->assertEqualsCanonicalizing([
            'admin.roles.update',
            'admin.roles.show',
            'admin.roles.destroy',
            'admin.groups.sync',
        ], $audits
            ->pluck('context.route_name')
            ->unique()
            ->values()
            ->all());
        $this->assertSame(
            ['permission_denied'],
            $audits->pluck('context.reason_code')->unique()->values()->all(),
        );
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_authorized_admin_identifier_misses_reach_explicit_not_found_responses(): void
    {
        $superAdmin = $this->userWithAccess(User::ROLE_SUPERADMIN);
        $role = Role::query()->create([
            'name' => 'lookup-control-role',
            'guard_name' => 'web',
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.users.show', 999999))
            ->assertNotFound();
        $this->actingAs($superAdmin)
            ->get(route('admin.roles.show', 999999))
            ->assertNotFound();
        $this->actingAs($superAdmin)
            ->post(
                '/admin/groups/999999/mappings',
                ['role_id' => $role->id],
            )
            ->assertNotFound();
        $this->actingAs($superAdmin)
            ->delete('/admin/groups/mappings/999999')
            ->assertNotFound();
    }

    private function userWithAccess(string $accessLevel): User
    {
        return User::factory()
            ->accessLevel($accessLevel)
            ->create()
            ->refresh();
    }
}
