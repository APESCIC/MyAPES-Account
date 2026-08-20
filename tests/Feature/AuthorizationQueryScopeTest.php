<?php

namespace Tests\Feature;

use App\Models\DirectoryGroup;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleSource;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\AuthorizationProfile;
use App\Services\AuthorizationRoleMaterializer;
use App\Support\AuthorizationCompatibilityDatabaseGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthorizationQueryScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_recipient_and_admin_count_queries_exclude_suspended_users(): void
    {
        $administrator = $this->user(User::ROLE_ADMIN);
        $activeStaff = $this->user(User::ROLE_STAFF);
        $suspendedStaff = $this->user(User::ROLE_STAFF, [
            'suspended_at' => now(),
            'suspension_reason' => 'Leave',
        ]);

        $this->assertSame(
            [$administrator->id, $activeStaff->id],
            User::query()->eligibleStaff()->orderBy('id')->pluck('id')->all(),
        );

        $accounts = $this->actingAs($administrator)
            ->get(route('admin.index'))
            ->assertOk()
            ->viewData('dashboard')['accounts'];

        $this->assertSame(3, $accounts['total']);
        $this->assertSame(1, $accounts['suspended']);
        $this->assertSame(1, $accounts['by_access_class'][AuthorizationProfile::ROLE_ADMINISTRATOR]);
        $this->assertSame(2, $accounts['by_access_class'][AuthorizationProfile::ROLE_STAFF]);

        $this->assertNotSame($activeStaff->id, $suspendedStaff->id);
    }

    public function test_suspended_staff_cannot_be_selected_as_an_assignee(): void
    {
        $owner = $this->user(User::ROLE_SERVICE_USER);
        $staff = $this->user(User::ROLE_STAFF);
        $suspendedStaff = $this->user(User::ROLE_STAFF, [
            'suspended_at' => now(),
            'suspension_reason' => 'Leave',
        ]);
        $ticket = SupportTicket::query()->create([
            'user_id' => $owner->id,
            'service_area' => 'it',
            'subject' => 'Assignment test',
            'priority' => 'medium',
            'status' => 'open',
            'description' => 'Suspended assignees are not eligible.',
        ]);

        $this->actingAs($staff)
            ->put(route('apes-cic.tickets.update', $ticket), [
                'status' => 'open',
                'priority' => 'medium',
                'assigned_to' => $suspendedStaff->id,
                'message' => null,
            ])
            ->assertSessionHasErrors('assigned_to');

        $this->assertNull($ticket->fresh()->assigned_to);
    }

    public function test_production_staff_eligibility_requires_correlated_directory_provenance(): void
    {
        $systemStaff = $this->user(User::ROLE_STAFF);
        $validDirectoryStaff = $this->user(User::ROLE_SERVICE_USER);
        $drifted = $this->user(User::ROLE_SERVICE_USER);
        $missingGroupStaff = $this->user(User::ROLE_SERVICE_USER);
        $unmappedGroupStaff = $this->user(User::ROLE_SERVICE_USER);
        $malformedSourceStaff = $this->user(User::ROLE_SERVICE_USER);
        $revokedDirectoryStaff = $this->permissionOnlyUser();
        $group = DirectoryGroup::query()
            ->where('name', 'myapes.staff')
            ->firstOrFail();
        $group->forceFill([
            'member_count' => 4,
            'status' => DirectoryGroup::STATUS_PRESENT,
        ])->save();
        $missingGroup = DirectoryGroup::query()->create([
            'name' => 'missing.staff',
            'member_count' => 0,
            'status' => DirectoryGroup::STATUS_MISSING,
        ]);
        $unmappedGroup = DirectoryGroup::query()->create([
            'name' => 'unmapped.staff',
            'member_count' => 1,
            'status' => DirectoryGroup::STATUS_PRESENT,
        ]);
        $malformedSourceGroup = DirectoryGroup::query()->create([
            'name' => 'malformed-source.staff',
            'member_count' => 1,
            'status' => DirectoryGroup::STATUS_PRESENT,
        ]);
        $staffRole = Role::query()
            ->where('guard_name', 'web')
            ->where('name', AuthorizationProfile::ROLE_STAFF)
            ->firstOrFail();
        $administratorRole = Role::query()
            ->where('guard_name', 'web')
            ->where('name', AuthorizationProfile::ROLE_ADMINISTRATOR)
            ->firstOrFail();
        foreach ([$missingGroup, $malformedSourceGroup] as $mappedGroup) {
            DB::table('directory_group_role_mappings')->insert([
                'directory_group_id' => $mappedGroup->id,
                'role_id' => $staffRole->id,
                'is_immutable' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $materializer = app(AuthorizationRoleMaterializer::class);

        $materializer->grant(
            $validDirectoryStaff,
            $staffRole,
            RoleSource::SOURCE_DIRECTORY,
            $group,
        );
        $materializer->grant(
            $drifted,
            $staffRole,
            RoleSource::SOURCE_DIRECTORY,
            $group,
        );
        $materializer->grant(
            $missingGroupStaff,
            $staffRole,
            RoleSource::SOURCE_DIRECTORY,
            $missingGroup,
        );
        $materializer->grant(
            $unmappedGroupStaff,
            $staffRole,
            RoleSource::SOURCE_DIRECTORY,
            $unmappedGroup,
        );
        $malformedSource = $materializer->grant(
            $malformedSourceStaff,
            $staffRole,
            RoleSource::SOURCE_DIRECTORY,
            $malformedSourceGroup,
        );
        $malformedSource->forceFill([
            'source_key' => 'directory:wrong-group',
        ])->save();
        $materializer->grant(
            $revokedDirectoryStaff,
            $staffRole,
            RoleSource::SOURCE_DIRECTORY,
            $group,
        );
        $materializer->revoke(
            $revokedDirectoryStaff,
            $staffRole,
            RoleSource::SOURCE_DIRECTORY,
            $group,
        );
        $guard = app(AuthorizationCompatibilityDatabaseGuard::class);
        $guard->drop();
        try {
            DB::table('model_has_roles')
                ->where('role_id', $staffRole->id)
                ->where('model_type', $drifted->getMorphClass())
                ->where('model_id', $drifted->id)
                ->delete();
        } finally {
            $guard->install();
        }
        $materializer->grant(
            $drifted,
            $administratorRole,
            RoleSource::SOURCE_SYSTEM,
        );
        $this->app->detectEnvironment(
            static fn (): string => 'production',
        );

        $this->assertSame(
            [$validDirectoryStaff->id],
            User::query()
                ->eligibleStaff()
                ->orderBy('id')
                ->pluck('id')
                ->all(),
        );
        $this->assertNotSame($systemStaff->id, $validDirectoryStaff->id);
        $this->assertTrue(
            $revokedDirectoryStaff->roles()
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
