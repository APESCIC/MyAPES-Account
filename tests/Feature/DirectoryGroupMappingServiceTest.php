<?php

namespace Tests\Feature;

use App\Exceptions\AuthorizationMutationDenied;
use App\Models\DirectoryGroup;
use App\Models\DirectoryGroupRoleMapping;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthorizationProfile;
use App\Services\DirectoryGroupMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectoryGroupMappingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_mapping_mutations_are_rejected_for_preset_groups(): void
    {
        $actor = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_SUPER_ADMIN)
            ->create();
        $group = DirectoryGroup::query()
            ->where('name', 'myapesaccount.staff')
            ->firstOrFail();
        $role = Role::query()
            ->where('name', AuthorizationProfile::ROLE_STAFF)
            ->firstOrFail();

        $service = app(DirectoryGroupMappingService::class);

        try {
            $service->map($actor, $group, $role);
            $this->fail('Preset directory mappings should not be mutable.');
        } catch (AuthorizationMutationDenied $exception) {
            $this->assertSame('preset_groups_only', $exception->reasonCode);
        }

        try {
            $service->setAppEnabled($actor, $group, false);
            $this->fail('Preset directory groups should remain enabled.');
        } catch (AuthorizationMutationDenied $exception) {
            $this->assertSame('preset_groups_only', $exception->reasonCode);
        }
    }

    public function test_legacy_myapes_groups_are_not_eligible_for_mapping(): void
    {
        $actor = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_SUPER_ADMIN)
            ->create();
        $group = DirectoryGroup::query()->create([
            'name' => 'myapes.staff',
            'status' => DirectoryGroup::STATUS_PRESENT,
            'app_enabled' => true,
        ]);

        $this->expectException(AuthorizationMutationDenied::class);

        app(DirectoryGroupMappingService::class)->assertManagedGroup($group);
    }

    public function test_immutable_mapping_removal_is_rejected(): void
    {
        $actor = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_SUPER_ADMIN)
            ->create();
        $group = DirectoryGroup::query()
            ->where('name', 'myapesaccount.admin')
            ->firstOrFail();
        $role = Role::query()
            ->where('name', AuthorizationProfile::ROLE_ADMINISTRATOR)
            ->firstOrFail();
        $mapping = DirectoryGroupRoleMapping::query()
            ->where('directory_group_id', $group->id)
            ->where('role_id', $role->id)
            ->firstOrFail();

        try {
            app(DirectoryGroupMappingService::class)->remove($actor, $mapping);
            $this->fail('Immutable preset mappings should not be removable.');
        } catch (AuthorizationMutationDenied $exception) {
            $this->assertSame('preset_groups_only', $exception->reasonCode);
        }
    }
}
