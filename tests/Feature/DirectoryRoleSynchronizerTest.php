<?php

namespace Tests\Feature;

use App\Models\DirectoryGroup;
use App\Models\DirectoryGroupRoleMapping;
use App\Models\Role;
use App\Models\RoleSource;
use App\Models\User;
use App\Services\AuthorizationRoleMaterializer;
use App\Services\DirectoryRoleSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectoryRoleSynchronizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_highest_protected_mapping_wins_and_custom_provenance_is_preserved(): void
    {
        $user = User::factory()
            ->accessLevel(User::ROLE_STAFF)
            ->cloudronIdentity('mapping-subject')
            ->create()
            ->refresh();
        [$customRole, $customGroup] = $this->customMapping();
        app(AuthorizationRoleMaterializer::class)->grant(
            $user,
            $customRole,
            RoleSource::SOURCE_LOCAL,
            actor: $user,
        );

        $result = app(DirectoryRoleSynchronizer::class)->synchronize($user, [
            'myapes.staff',
            'myapes.admin',
            $customGroup->name,
        ]);

        $this->assertTrue($result->eligible);
        $this->assertSame('administrator', $result->protectedRole);
        $this->assertSame(
            ['administrator', 'custom-reviewer'],
            $user->fresh()->roles()->orderBy('name')->pluck('name')->all(),
        );
        $this->assertDatabaseHas('role_sources', [
            'user_id' => $user->id,
            'role_id' => $customRole->id,
            'source' => RoleSource::SOURCE_LOCAL,
            'directory_group_id' => null,
        ]);
        $this->assertDatabaseHas('role_sources', [
            'user_id' => $user->id,
            'role_id' => $customRole->id,
            'source' => RoleSource::SOURCE_DIRECTORY,
            'directory_group_id' => $customGroup->id,
        ]);
        $this->assertDatabaseMissing('role_sources', [
            'user_id' => $user->id,
            'source' => RoleSource::SOURCE_DIRECTORY,
            'directory_group_id' => DirectoryGroup::query()
                ->where('name', 'myapes.staff')
                ->value('id'),
        ]);
    }

    public function test_loss_of_protected_eligibility_removes_directory_sources_only(): void
    {
        $user = User::factory()
            ->accessLevel(User::ROLE_STAFF)
            ->cloudronIdentity('revocation-subject')
            ->create()
            ->refresh();
        [$customRole, $customGroup] = $this->customMapping();
        $roles = app(AuthorizationRoleMaterializer::class);
        $roles->grant(
            $user,
            $customRole,
            RoleSource::SOURCE_LOCAL,
            actor: $user,
        );
        app(DirectoryRoleSynchronizer::class)->synchronize($user, [
            'myapes.staff',
            $customGroup->name,
        ]);
        $epoch = $user->fresh()->authorization_epoch;

        $result = app(DirectoryRoleSynchronizer::class)->synchronize(
            $user->fresh(),
            [$customGroup->name],
        );

        $this->assertFalse($result->eligible);
        $user->refresh();
        $this->assertSame(
            ['custom-reviewer', 'service-user'],
            $user->roles()->orderBy('name')->pluck('name')->all(),
        );
        $this->assertSame($epoch + 1, $user->authorization_epoch);
        $this->assertSame('service_user', $user->legacy_access_level);
        $this->assertDatabaseMissing('role_sources', [
            'user_id' => $user->id,
            'source' => RoleSource::SOURCE_DIRECTORY,
        ]);
        $this->assertDatabaseHas('role_sources', [
            'user_id' => $user->id,
            'role_id' => $customRole->id,
            'source' => RoleSource::SOURCE_LOCAL,
        ]);
    }

    public function test_directory_loss_rotates_session_state_when_local_sources_keep_the_same_roles(): void
    {
        $user = User::factory()
            ->accessLevel(User::ROLE_SERVICE_USER)
            ->cloudronIdentity('overlapping-revocation-subject')
            ->create()
            ->refresh();
        $materializer = app(AuthorizationRoleMaterializer::class);
        $staffRole = Role::query()->where('name', 'staff')->firstOrFail();
        $serviceUserRole = Role::query()
            ->where('name', 'service-user')
            ->firstOrFail();
        $materializer->grant(
            $user,
            $staffRole,
            RoleSource::SOURCE_SYSTEM,
        );
        $materializer->grant(
            $user,
            $serviceUserRole,
            RoleSource::SOURCE_SYSTEM,
        );

        $synchronizer = app(DirectoryRoleSynchronizer::class);
        $synchronizer->synchronize($user, ['myapes.staff']);
        $user->refresh()->forceFill([
            'remember_token' => 'remember-before-directory-loss',
        ])->save();
        $epoch = $user->authorization_epoch;

        $result = $synchronizer->synchronize($user->fresh(), []);

        $this->assertFalse($result->eligible);
        $this->assertTrue($result->authorizationChanged);
        $user->refresh();
        $this->assertSame($epoch + 1, $user->authorization_epoch);
        $this->assertNotSame(
            'remember-before-directory-loss',
            $user->getRememberToken(),
        );
        $this->assertSame(
            ['service-user', 'staff'],
            $user->roles()->orderBy('name')->pluck('name')->all(),
        );
        $this->assertDatabaseMissing('role_sources', [
            'user_id' => $user->id,
            'source' => RoleSource::SOURCE_DIRECTORY,
        ]);
    }

    public function test_activation_can_defer_role_change_invalidation_to_its_single_cutover_rotation(): void
    {
        $user = User::factory()
            ->accessLevel(User::ROLE_STAFF)
            ->cloudronIdentity('activation-subject')
            ->create()
            ->refresh();
        $user->forceFill([
            'remember_token' => 'remember-before-activation',
        ])->save();
        $user = User::query()->findOrFail($user->id);
        $epoch = $user->authorization_epoch;

        $result = app(DirectoryRoleSynchronizer::class)->synchronize(
            $user,
            ['myapes.admin'],
            false,
        );

        $this->assertTrue($result->authorizationChanged);
        $user->refresh();
        $this->assertSame($epoch, $user->authorization_epoch);
        $this->assertSame(
            'remember-before-activation',
            $user->getRememberToken(),
        );
        $this->assertSame(
            ['administrator'],
            $user->roles()->pluck('name')->all(),
        );
    }

    public function test_disabled_app_group_mappings_are_ignored_until_reenabled(): void
    {
        $user = User::factory()
            ->accessLevel(User::ROLE_SERVICE_USER)
            ->cloudronIdentity('disabled-group-subject')
            ->create()
            ->refresh();
        [$customRole, $customGroup] = $this->customMapping();
        $customGroup->forceFill(['app_enabled' => false])->save();

        $synchronizer = app(DirectoryRoleSynchronizer::class);
        $disabled = $synchronizer->synchronize($user, [
            $customGroup->name,
            'myapes.staff',
        ]);

        $this->assertTrue($disabled->eligible);
        $this->assertSame('staff', $disabled->protectedRole);
        $this->assertTrue(
            $user->fresh()->roles()->where('name', 'staff')->exists(),
        );
        $this->assertFalse(
            $user->fresh()->roles()->where('name', 'custom-reviewer')->exists(),
        );
        $this->assertDatabaseMissing('role_sources', [
            'user_id' => $user->id,
            'role_id' => $customRole->id,
            'source' => RoleSource::SOURCE_DIRECTORY,
        ]);

        $customGroup->forceFill(['app_enabled' => true])->save();
        $enabled = $synchronizer->synchronize($user->fresh(), [
            $customGroup->name,
            'myapes.staff',
        ]);

        $this->assertTrue($enabled->eligible);
        $this->assertTrue(
            $user->fresh()->roles()->where('name', 'custom-reviewer')->exists(),
        );
        $this->assertTrue(
            $user->fresh()->roles()->where('name', 'staff')->exists(),
        );
        $this->assertDatabaseHas('role_sources', [
            'user_id' => $user->id,
            'role_id' => $customRole->id,
            'source' => RoleSource::SOURCE_DIRECTORY,
            'directory_group_id' => $customGroup->id,
        ]);
    }

    /**
     * @return array{Role, DirectoryGroup}
     */
    private function customMapping(): array
    {
        $role = Role::query()->create([
            'name' => 'custom-reviewer',
            'guard_name' => 'web',
        ]);
        $group = DirectoryGroup::query()->create([
            'name' => 'myapes.custom-reviewer',
            'status' => DirectoryGroup::STATUS_PRESENT,
            'app_enabled' => true,
        ]);
        (new DirectoryGroupRoleMapping)->forceFill([
            'directory_group_id' => $group->id,
            'role_id' => $role->id,
            'is_immutable' => false,
        ])->save();

        return [$role, $group];
    }
}
