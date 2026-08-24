<?php

namespace Tests\Feature;

use App\Models\DirectoryGroup;
use App\Models\Role;
use App\Models\RoleSource;
use App\Models\User;
use App\Services\AuthorizationMutationService;
use App\Services\AuthorizationProfile;
use App\Services\DirectoryRoleSynchronizer;
use App\Services\SessionAuthorizationContext;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationMutationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspension_is_transactional_and_invalidates_existing_authorization(): void
    {
        $actor = $this->userWithAccess(User::ROLE_SUPERADMIN);
        $target = $this->userWithAccess(User::ROLE_ADMIN);
        $originalToken = $target->remember_token;
        $this->actingAs($actor);

        app(AuthorizationMutationService::class)->suspend(
            $target,
            $actor,
            'Security review',
        );

        $target->refresh();
        $this->assertNotNull($target->suspended_at);
        $this->assertSame($actor->id, $target->suspended_by);
        $this->assertSame('Security review', $target->suspension_reason);
        $this->assertSame(2, $target->authorization_epoch);
        $this->assertNotSame($originalToken, $target->remember_token);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'authorization.user_suspended',
            'user_id' => $actor->id,
            'auditable_id' => $target->id,
        ]);
    }

    public function test_self_change_and_non_super_admin_changes_to_super_admins_are_rejected(): void
    {
        $first = $this->userWithAccess(User::ROLE_SUPERADMIN);
        $second = $this->userWithAccess(User::ROLE_SUPERADMIN);
        $this->actingAs($first);
        app(AuthorizationMutationService::class)->suspend(
            $second,
            $first,
            'Leave',
        );

        try {
            app(AuthorizationMutationService::class)->suspend(
                $first,
                $first,
                'Self change',
            );
            $this->fail('Self-suspension was accepted.');
        } catch (DomainException $exception) {
            $this->assertSame(
                'Actors cannot change their own authorization state.',
                $exception->getMessage(),
            );
        }

        $otherActor = $this->userWithAccess(User::ROLE_ADMIN);
        $this->actingAs($otherActor);

        try {
            app(AuthorizationMutationService::class)->suspend(
                $first,
                $otherActor,
                'Final administrator',
            );
            $this->fail('The final active super-admin was suspended.');
        } catch (DomainException $exception) {
            $this->assertSame(
                'Only an active super-admin may affect administrator targets.',
                $exception->getMessage(),
            );
        }

        $first->refresh();
        $this->assertNull($first->suspended_at);
        $this->assertSame(1, $first->authorization_epoch);
    }

    public function test_reactivation_and_role_changes_rotate_authorization_state(): void
    {
        $actor = $this->userWithAccess(User::ROLE_SUPERADMIN);
        $target = $this->userWithAccess(User::ROLE_STAFF);
        $this->actingAs($actor);
        $service = app(AuthorizationMutationService::class);
        $service->suspend($target, $actor, 'Leave');
        $suspendedToken = $target->fresh()->remember_token;

        $service->reactivate($target->fresh(), $actor);
        $target->refresh();
        $this->assertNull($target->suspended_at);
        $this->assertSame(3, $target->authorization_epoch);
        $this->assertNotSame($suspendedToken, $target->remember_token);

        $customRole = Role::query()->create([
            'name' => 'case-reviewer',
            'guard_name' => 'web',
        ]);
        $service->grantLocalRole($target, $customRole, $actor);
        $this->assertSame(4, $target->fresh()->authorization_epoch);
        $this->assertTrue($target->fresh()->roles->contains($customRole));

        $service->revokeLocalRole($target, $customRole, $actor);
        $this->assertSame(5, $target->fresh()->authorization_epoch);
        $this->assertFalse($target->fresh()->roles->contains($customRole));
    }

    public function test_custom_role_management_enforces_actor_and_target_boundaries(): void
    {
        $superAdmin = $this->userWithAccess(User::ROLE_SUPERADMIN);
        $administrator = $this->userWithAccess(User::ROLE_ADMIN);
        $target = $this->userWithAccess(User::ROLE_SERVICE_USER);
        $administratorTarget = $this->userWithAccess(User::ROLE_ADMIN);
        $customRole = Role::query()->create([
            'name' => 'case-reviewer',
            'guard_name' => 'web',
        ]);
        $service = app(AuthorizationMutationService::class);

        $this->actingAs($administrator);
        $service->grantLocalRole($target, $customRole, $administrator);
        $this->assertTrue($target->fresh()->roles->contains($customRole));

        try {
            $service->grantLocalRole(
                $administratorTarget,
                $customRole,
                $administrator,
            );
            $this->fail('An administrator affected an administrator target.');
        } catch (DomainException $exception) {
            $this->assertSame(
                'Only an active super-admin may affect administrator targets.',
                $exception->getMessage(),
            );
        }

        $this->actingAs($superAdmin);
        try {
            $service->grantLocalRole($superAdmin, $customRole, $superAdmin);
            $this->fail('A super-admin changed their own roles.');
        } catch (DomainException $exception) {
            $this->assertSame(
                'Actors cannot change their own authorization state.',
                $exception->getMessage(),
            );
        }

        $this->assertTrue($target->fresh()->roles->contains($customRole));
        $this->assertFalse(
            $administratorTarget->fresh()->roles->contains($customRole),
        );
        $this->assertFalse($superAdmin->fresh()->roles->contains($customRole));
    }

    public function test_user_mutations_revalidate_directory_eligibility_inside_the_transaction(): void
    {
        $group = DirectoryGroup::query()
            ->where('name', 'myapesaccount.admin')
            ->firstOrFail();
        $group->forceFill([
            'status' => DirectoryGroup::STATUS_PRESENT,
            'member_count' => 1,
        ])->save();
        $actor = User::factory()
            ->cloudronIdentity('transaction-fence-administrator')
            ->create(['ldap_groups' => [$group->name]])
            ->refresh();
        $target = $this->userWithAccess(User::ROLE_SERVICE_USER);
        app(DirectoryRoleSynchronizer::class)->synchronize(
            $actor,
            [$group->name],
        );
        $administratorRoleId = Role::query()
            ->where('name', AuthorizationProfile::ROLE_ADMINISTRATOR)
            ->value('id');
        RoleSource::query()
            ->whereBelongsTo($actor)
            ->where('role_id', $administratorRoleId)
            ->where('source', '<>', RoleSource::SOURCE_DIRECTORY)
            ->delete();
        $this->authenticateQaContext($actor->fresh());

        $group->forceFill([
            'status' => DirectoryGroup::STATUS_MISSING,
            'member_count' => 0,
        ])->save();

        try {
            app(AuthorizationMutationService::class)->suspend(
                $target,
                $actor,
                'Stale directory eligibility',
            );
            $this->fail('A directory-revoked administrator suspended a user.');
        } catch (DomainException $exception) {
            $this->assertSame(
                'Only an active administrator may manage users.',
                $exception->getMessage(),
            );
        }

        $this->assertNull($target->fresh()->suspended_at);
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
