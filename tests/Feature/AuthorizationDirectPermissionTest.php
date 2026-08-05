<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\PermissionSource;
use App\Models\User;
use App\Services\AuthorizationDirectPermissionMaterializer;
use App\Services\SessionAuthorizationContext;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class AuthorizationDirectPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_materializer_grants_and_revokes_a_safe_direct_wildcard(): void
    {
        $staff = User::factory()->accessLevel(User::ROLE_STAFF)->create();
        $permission = Permission::query()->create([
            'name' => 'admin.users.*',
            'guard_name' => 'web',
        ]);
        $materializer = app(AuthorizationDirectPermissionMaterializer::class);

        $source = $materializer->grant(
            $staff,
            $permission,
            PermissionSource::SOURCE_LOCAL,
            actor: $staff,
        );

        $this->assertNull($source->team_id);
        $this->assertDatabaseHas('permission_sources', [
            'user_id' => $staff->id,
            'permission_id' => $permission->id,
            'team_id' => null,
            'source' => PermissionSource::SOURCE_LOCAL,
            'source_key' => PermissionSource::SOURCE_LOCAL,
            'granted_by' => $staff->id,
        ]);
        $this->assertDatabaseHas('model_has_permissions', [
            'permission_id' => $permission->id,
            'model_type' => User::class,
            'model_id' => $staff->id,
            'team_id' => null,
        ]);

        $this->actingAsWithQaContext($staff);
        $this->assertTrue(Gate::allows('admin.users.view'));
        $this->assertTrue(Gate::allows('admin.users.manage'));
        $this->assertFalse(Gate::allows('admin.roles.manage'));

        $materializer->revoke(
            $staff,
            $permission,
            PermissionSource::SOURCE_LOCAL,
        );

        $this->assertDatabaseMissing('permission_sources', [
            'user_id' => $staff->id,
            'permission_id' => $permission->id,
        ]);
        $this->assertDatabaseMissing('model_has_permissions', [
            'permission_id' => $permission->id,
            'model_type' => User::class,
            'model_id' => $staff->id,
        ]);
    }

    public function test_direct_permissions_do_not_bypass_session_or_provenance_eligibility(): void
    {
        $serviceUser = User::factory()
            ->accessLevel(User::ROLE_SERVICE_USER)
            ->create();
        $permission = Permission::query()->create([
            'name' => 'admin.users.*',
            'guard_name' => 'web',
        ]);
        app(AuthorizationDirectPermissionMaterializer::class)->grant(
            $serviceUser,
            $permission,
            PermissionSource::SOURCE_SYSTEM,
        );

        $this->actingAsWithQaContext($serviceUser);

        $this->assertFalse(Gate::allows('admin.users.view'));
    }

    public function test_direct_admin_access_does_not_expand_to_user_identity_permissions(): void
    {
        $staff = User::factory()->accessLevel(User::ROLE_STAFF)->create();
        $permission = Permission::query()
            ->where('name', 'admin.access')
            ->where('guard_name', 'web')
            ->firstOrFail();
        app(AuthorizationDirectPermissionMaterializer::class)->grant(
            $staff,
            $permission,
            PermissionSource::SOURCE_SYSTEM,
        );

        $this->actingAsWithQaContext($staff);

        $this->assertTrue(Gate::allows('admin.access'));
        $this->assertFalse(Gate::allows('admin.users.view'));
    }

    public function test_direct_permission_pivots_still_require_central_provenance(): void
    {
        $user = User::factory()->accessLevel(User::ROLE_STAFF)->create();
        $permission = Permission::query()
            ->where('name', 'admin.users.view')
            ->firstOrFail();

        $this->expectException(QueryException::class);

        $user->permissions()->attach($permission->id);
    }

    public function test_central_materializer_rejects_permissions_outside_the_catalogue(): void
    {
        $user = User::factory()->accessLevel(User::ROLE_STAFF)->create();
        $permission = Permission::query()->create([
            'name' => 'unrelated.*',
            'guard_name' => 'web',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Direct permissions must resolve within the application catalogue.',
        );

        app(AuthorizationDirectPermissionMaterializer::class)->grant(
            $user,
            $permission,
            PermissionSource::SOURCE_SYSTEM,
        );
    }

    public function test_maintenance_downgrade_rejects_direct_permission_state(): void
    {
        $user = User::factory()->accessLevel(User::ROLE_STAFF)->create();
        $permission = Permission::query()
            ->where('name', 'admin.users.view')
            ->firstOrFail();
        app(AuthorizationDirectPermissionMaterializer::class)->grant(
            $user,
            $permission,
            PermissionSource::SOURCE_SYSTEM,
        );
        $this->fakeMaintenanceMode();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Maintenance downgrade cannot represent direct user permissions.',
        );

        $this->authorizationMigration()->down();
    }

    private function actingAsWithQaContext(User $user): void
    {
        if (! request()->hasSession()) {
            request()->setLaravelSession(app('session')->driver());
        }

        $this->actingAs($user);
        request()->session()->put(
            app(SessionAuthorizationContext::class)->valuesFor(
                $user->refresh(),
                SessionAuthorizationContext::METHOD_QA,
            ),
        );
    }

    private function authorizationMigration(): Migration
    {
        /** @var Migration $migration */
        $migration = require database_path(
            'migrations/2026_07_28_000100_cut_over_authorization_domain.php',
        );

        return $migration;
    }
}
