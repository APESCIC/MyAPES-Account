<?php

namespace Tests\Feature;

use App\Models\DirectoryGroup;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleSource;
use App\Models\User;
use App\Services\AuthorizationRoleMaterializer;
use App\Support\AuthorizationCompatibilityDatabaseGuard;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthorizationRoleMaterializerTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_grants_materialize_an_effective_role_union_without_direct_permissions(): void
    {
        $this->assertTrue(Schema::hasTable('role_sources'));

        $user = User::factory()->create();
        $role = Role::query()->create([
            'name' => 'case-reviewer',
            'guard_name' => 'web',
            'is_protected' => false,
        ]);
        $directoryGroup = DirectoryGroup::query()->create([
            'name' => 'myapes.case-reviewers',
            'status' => DirectoryGroup::STATUS_PRESENT,
        ]);
        $materializer = app(AuthorizationRoleMaterializer::class);

        $materializer->grant(
            $user,
            $role,
            RoleSource::SOURCE_DIRECTORY,
            $directoryGroup,
        );
        $materializer->grant($user, $role, RoleSource::SOURCE_LOCAL, actor: $user);

        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->id,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
        $this->assertSame(
            [RoleSource::SOURCE_DIRECTORY, RoleSource::SOURCE_LOCAL],
            RoleSource::query()
                ->whereBelongsTo($user)
                ->whereBelongsTo($role)
                ->orderBy('source')
                ->pluck('source')
                ->all(),
        );
        $this->assertDatabaseCount('model_has_permissions', 0);

        $materializer->revoke(
            $user,
            $role,
            RoleSource::SOURCE_DIRECTORY,
            $directoryGroup,
        );
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->id,
            'model_id' => $user->id,
        ]);

        $materializer->revoke($user, $role, RoleSource::SOURCE_LOCAL);
        $this->assertDatabaseMissing('model_has_roles', [
            'role_id' => $role->id,
            'model_id' => $user->id,
        ]);
    }

    public function test_materializer_rejects_unknown_source_types(): void
    {
        $this->assertTrue(Schema::hasTable('role_sources'));

        $this->expectException(InvalidArgumentException::class);

        app(AuthorizationRoleMaterializer::class)->grant(
            User::factory()->create(),
            Role::query()->where('name', 'staff')->firstOrFail(),
            'manual',
        );
    }

    public function test_local_role_grants_require_an_actor(): void
    {
        $role = Role::query()->create([
            'name' => 'actor-required-reviewer',
            'guard_name' => 'web',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Local role sources require a persisted actor.',
        );

        app(AuthorizationRoleMaterializer::class)->grant(
            User::factory()->create(),
            $role,
            RoleSource::SOURCE_LOCAL,
        );
    }

    public function test_local_role_grants_require_a_persisted_actor(): void
    {
        $role = Role::query()->create([
            'name' => 'persisted-actor-required-reviewer',
            'guard_name' => 'web',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Local role sources require a persisted actor.',
        );

        app(AuthorizationRoleMaterializer::class)->grant(
            User::factory()->create(),
            $role,
            RoleSource::SOURCE_LOCAL,
            actor: new User,
        );
    }

    #[DataProvider('nonLocalRoleSourceProvider')]
    public function test_non_local_role_grants_reject_an_actor(string $source): void
    {
        $user = User::factory()->create();
        $actor = User::factory()->create();
        $role = Role::query()->where('name', 'staff')->firstOrFail();
        $directoryGroup = $source === RoleSource::SOURCE_DIRECTORY
            ? DirectoryGroup::query()->create([
                'name' => 'myapes.actor-rejection',
                'status' => DirectoryGroup::STATUS_PRESENT,
            ])
            : null;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Non-local role sources cannot reference an actor.',
        );

        app(AuthorizationRoleMaterializer::class)->grant(
            $user,
            $role,
            $source,
            $directoryGroup,
            $actor,
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonLocalRoleSourceProvider(): iterable
    {
        yield 'system' => [RoleSource::SOURCE_SYSTEM];
        yield 'directory' => [RoleSource::SOURCE_DIRECTORY];
        yield 'legacy compatibility' => [
            RoleSource::SOURCE_LEGACY_COMPATIBILITY,
        ];
    }

    public function test_materializer_rejects_transitional_provenance_for_custom_roles(): void
    {
        $role = Role::query()->create([
            'name' => 'custom-transitional-role',
            'guard_name' => 'web',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Legacy compatibility provenance is restricted to protected roles.',
        );

        app(AuthorizationRoleMaterializer::class)->grant(
            User::factory()->create(),
            $role,
            RoleSource::SOURCE_LEGACY_COMPATIBILITY,
        );
    }

    public function test_role_source_ledger_rejects_unknown_provenance(): void
    {
        $this->assertTrue(Schema::hasTable('role_sources'));

        $user = User::factory()->create();
        $role = Role::query()->where('name', 'staff')->firstOrFail();

        $this->expectException(QueryException::class);

        DB::table('role_sources')->insert([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'source' => 'manual',
            'source_key' => 'manual',
            'directory_group_id' => null,
            'granted_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_user_authorization_fields_are_cast_and_related(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'authorization_epoch'));

        $actor = User::factory()->create();
        $user = User::factory()->create([
            'identity_type' => User::IDENTITY_HYBRID,
            'authorization_epoch' => 7,
            'suspended_at' => '2026-07-28 09:00:00',
            'suspended_by' => $actor->id,
            'suspension_reason' => str_repeat('a', 500),
        ]);

        $this->assertSame(7, $user->authorization_epoch);
        $this->assertSame(User::IDENTITY_HYBRID, $user->identity_type);
        $this->assertNotNull($user->suspended_at);
        $this->assertTrue($user->suspendedBy->is($actor));
        $this->assertSame(500, strlen($user->suspension_reason));
    }

    public function test_direct_user_permission_mutators_fail_closed(): void
    {
        $this->assertTrue(Schema::hasTable('permissions'));

        $user = User::factory()->create();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Direct user permissions are disabled.');

        $user->givePermissionTo('staff.access');
    }

    public function test_direct_user_role_mutators_fail_closed(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->where('name', 'staff')->firstOrFail();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unprovenanced user role mutations are disabled.');

        $user->assignRole($role);
    }

    public function test_role_relation_rejects_unprovenanced_attach(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'name' => 'relation-attached-role',
            'guard_name' => 'web',
        ]);

        $this->expectException(QueryException::class);

        $user->roles()->attach($role->id);
    }

    public function test_permission_relation_rejects_direct_attach(): void
    {
        $user = User::factory()->create();
        $permission = Permission::query()->where('name', 'staff.access')->firstOrFail();

        $this->expectException(QueryException::class);

        $user->permissions()->attach($permission->id);
    }

    public function test_role_relation_cannot_detach_a_provenanced_role(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->where('name', 'service-user')->firstOrFail();

        $this->expectException(QueryException::class);

        $user->roles()->detach($role->id);
    }

    public function test_revoke_repairs_a_missing_pivot_when_another_source_remains(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'name' => 'concurrent-reviewer',
            'guard_name' => 'web',
        ]);
        $directoryGroup = DirectoryGroup::query()->create([
            'name' => 'myapes.concurrent-reviewers',
        ]);
        $materializer = app(AuthorizationRoleMaterializer::class);
        $materializer->grant($user, $role, RoleSource::SOURCE_LOCAL, actor: $user);
        $materializer->grant(
            $user,
            $role,
            RoleSource::SOURCE_DIRECTORY,
            $directoryGroup,
        );

        $guard = app(AuthorizationCompatibilityDatabaseGuard::class);
        $guard->drop();
        DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->where('model_type', User::class)
            ->where('model_id', $user->id)
            ->delete();
        $guard->install();

        $materializer->revoke(
            $user,
            $role,
            RoleSource::SOURCE_DIRECTORY,
            $directoryGroup,
        );

        $this->assertDatabaseHas('role_sources', [
            'user_id' => $user->id,
            'role_id' => $role->id,
            'source' => RoleSource::SOURCE_LOCAL,
        ]);
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->id,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
    }
}
