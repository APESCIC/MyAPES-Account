<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleSource;
use App\Models\User;
use App\Services\AuthorizationProfile;
use App\Services\AuthorizationRoleMaterializer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminShellNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_staff_never_see_admin_primary_nav(): void
    {
        $public = User::factory()->accessLevel(User::ROLE_SERVICE_USER)->create();
        $staff = User::factory()->accessLevel(User::ROLE_STAFF)->create();

        foreach ([$public, $staff] as $actor) {
            $this->actingAs($actor)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertDontSee('>Admin</span>', false)
                ->assertDontSee('>Super Admin</span>', false)
                ->assertDontSee('href="'.route('admin.index').'"', false)
                ->assertDontSee('href="'.route('superadmin.index').'"', false);
        }
    }

    public function test_administrator_sees_single_admin_primary_nav_without_super_admin_door(): void
    {
        $admin = User::factory()->accessLevel(User::ROLE_ADMIN)->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('>Admin</span>', false)
            ->assertDontSee('>Super Admin</span>', false)
            ->assertSee('href="'.route('admin.index').'"', false)
            ->assertDontSee('href="'.route('superadmin.index').'"', false);
    }

    public function test_super_admin_sees_single_admin_primary_nav_covering_all_children(): void
    {
        $superAdmin = User::factory()->accessLevel(User::ROLE_SUPERADMIN)->create();

        $this->actingAs($superAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('>Admin</span>', false)
            ->assertDontSee('>Super Admin</span>', false)
            ->assertSee('href="'.route('admin.index').'"', false)
            ->assertDontSee('href="'.route('superadmin.index').'"', false);

        $this->actingAs($superAdmin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('>Overview</a>', false)
            ->assertSee('>Public users</a>', false)
            ->assertSee('>Staff</a>', false)
            ->assertSee('>Access</a>', false)
            ->assertSee('>Plugins</a>', false)
            ->assertSee('>Maintenance</a>', false);
    }

    public function test_administrator_submenu_hides_super_admin_only_children(): void
    {
        $admin = User::factory()->accessLevel(User::ROLE_ADMIN)->create();

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('>Overview</a>', false)
            ->assertSee('>Public users</a>', false)
            ->assertSee('>Staff</a>', false)
            ->assertDontSee('>Access</a>', false)
            ->assertDontSee('>Plugins</a>', false)
            ->assertDontSee('>Maintenance</a>', false);
    }

    public function test_admin_child_urls_fail_closed_without_matching_permission(): void
    {
        $admin = User::factory()->accessLevel(User::ROLE_ADMIN)->create();
        $staff = User::factory()->accessLevel(User::ROLE_STAFF)->create();

        foreach (['/admin/access', '/admin/modules', '/admin/maintenance', '/superadmin'] as $path) {
            $this->actingAs($admin)->get($path)->assertForbidden();
            $this->actingAs($staff)->get($path)->assertForbidden();
        }

        $this->actingAs($staff)->get('/admin')->assertForbidden();
        $this->actingAs($staff)->get('/admin/users')->assertForbidden();
    }

    public function test_legacy_superadmin_paths_redirect_into_admin_equivalents(): void
    {
        $superAdmin = User::factory()->accessLevel(User::ROLE_SUPERADMIN)->create();

        $this->actingAs($superAdmin)
            ->get('/superadmin')
            ->assertRedirect(route('admin.index'));
        $this->actingAs($superAdmin)
            ->get('/superadmin?range=7')
            ->assertRedirect(route('admin.index', ['range' => 7]));
        $this->actingAs($superAdmin)
            ->get('/superadmin/plugins')
            ->assertRedirect(route('admin.modules.index'));
        $this->actingAs($superAdmin)
            ->get('/superadmin/modules')
            ->assertRedirect(route('admin.modules.index'));
        $this->actingAs($superAdmin)
            ->get('/superadmin/groups')
            ->assertRedirect(route('admin.groups.index'));

        $this->actingAs($superAdmin)
            ->followingRedirects()
            ->get('/superadmin')
            ->assertOk()
            ->assertSee('Admin overview')
            ->assertSee('data-kpi="enabled-modules"', false);
    }

    public function test_admin_access_shell_permission_does_not_open_protected_actions(): void
    {
        $actor = User::factory()->accessLevel(User::ROLE_STAFF)->create();
        $role = Role::query()->create([
            'name' => 'shell-access-only',
            'guard_name' => 'web',
        ]);
        $permission = Permission::query()
            ->where('name', AuthorizationProfile::PERMISSION_ADMIN_ACCESS)
            ->where('guard_name', 'web')
            ->firstOrFail();
        $role->permissions()->attach($permission->id);
        app(AuthorizationRoleMaterializer::class)->grant(
            $actor,
            $role,
            RoleSource::SOURCE_LOCAL,
            actor: $actor,
        );

        $this->actingAs($actor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('>Admin</span>', false);

        $this->actingAs($actor)->get(route('admin.index'))->assertForbidden();
        $this->actingAs($actor)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($actor)->get(route('admin.modules.index'))->assertForbidden();
        $this->actingAs($actor)->get(route('superadmin.index'))->assertForbidden();
    }

    public function test_superadmin_access_still_required_for_technical_overview_extras(): void
    {
        $admin = User::factory()->accessLevel(User::ROLE_ADMIN)->create();
        $superAdmin = User::factory()->accessLevel(User::ROLE_SUPERADMIN)->create();

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('data-kpi="total-accounts"', false)
            ->assertDontSee('data-kpi="enabled-modules"', false)
            ->assertDontSee('Created versus closed');

        $this->actingAs($superAdmin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('data-kpi="total-accounts"', false)
            ->assertSee('data-kpi="enabled-modules"', false)
            ->assertSee('Created versus closed');
    }
}
