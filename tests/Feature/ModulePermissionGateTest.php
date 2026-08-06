<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleSource;
use App\Models\User;
use App\Services\AuthorizationProfile;
use App\Services\AuthorizationRoleMaterializer;
use App\Services\ModuleInstallationSynchronizer;
use App\Services\SessionAuthorizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModulePermissionGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ModuleInstallationSynchronizer::class)->synchronize();
    }

    public function test_public_module_permissions_work_in_password_context_without_opening_staff_abilities(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(
            app(SessionAuthorizationContext::class)->valuesFor(
                $user,
                SessionAuthorizationContext::METHOD_PASSWORD,
            ),
        );

        $this->assertTrue($user->can('apes-cic.tickets.create'));
        $this->assertTrue($user->can('apes-cic.tickets.view-own'));
        $this->assertFalse($user->can('apes-cic.tickets.assign'));
        $this->assertFalse($user->can('shelter-rescue.cases.view-all'));
    }

    public function test_directory_restricted_module_permissions_follow_protected_staff_eligibility(): void
    {
        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create();
        $this->actingAs($staff);

        $this->assertTrue($staff->can('apes-cic.tickets.assign'));
        $this->assertTrue(
            $staff->can('pet-care-clinic.consultations.close'),
        );

        $serviceUser = User::factory()->create();
        $customRole = Role::query()->create([
            'name' => 'module-assignment-helper',
            'guard_name' => 'web',
            'is_protected' => false,
        ]);
        $customRole->permissions()->attach(
            Permission::query()
                ->where('name', 'apes-cic.tickets.assign')
                ->value('id'),
        );
        app(AuthorizationRoleMaterializer::class)->grant(
            $serviceUser,
            $customRole,
            RoleSource::SOURCE_LOCAL,
            actor: $staff,
        );
        $this->actingAs($serviceUser);

        $this->assertFalse($serviceUser->can('apes-cic.tickets.assign'));
    }

    public function test_module_administration_is_viewable_by_administrators_and_mutable_only_by_super_admins(): void
    {
        $administrator = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_ADMINISTRATOR)
            ->create();
        $this->actingAs($administrator);

        $this->assertTrue($administrator->can('admin.modules.view'));
        $this->assertFalse($administrator->can('admin.modules.manage'));

        $superAdmin = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_SUPER_ADMIN)
            ->create();
        $this->actingAs($superAdmin);

        $this->assertTrue($superAdmin->can('admin.modules.view'));
        $this->assertTrue($superAdmin->can('admin.modules.manage'));
    }

    public function test_suspension_denies_public_and_staff_module_permissions_immediately(): void
    {
        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create([
                'suspended_at' => now(),
                'suspension_reason' => 'policy',
            ]);
        $this->actingAs($staff);

        $this->assertFalse($staff->can('apes-cic.tickets.create'));
        $this->assertFalse($staff->can('apes-cic.tickets.assign'));
    }
}
