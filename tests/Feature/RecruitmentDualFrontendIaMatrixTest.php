<?php

namespace Tests\Feature;

use App\Models\ModuleInstallation;
use App\Models\Permission;
use App\Models\RecruitmentApplication;
use App\Models\RecruitmentRole;
use App\Models\Role;
use App\Models\User;
use App\Services\AuthorizationProfile;
use App\Services\ModuleInstallationSynchronizer;
use App\Services\ModuleSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RecruitmentDualFrontendIaMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ModuleInstallationSynchronizer::class)->synchronize();
    }

    public function test_guest_sees_public_recruitment_not_staff_manage(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('>Recruitment</span>', false)
            ->assertDontSee('>Recruit manage</span>', false)
            ->assertDontSee('href="'.route('apes-cic.recruitment.index').'"', false);

        $this->get(route('recruitment.index'))
            ->assertOk()
            ->assertSeeText('Open roles')
            ->assertDontSeeText('My applications')
            ->assertDontSee('>Recruit manage</span>', false)
            ->assertDontSee('aria-label="Recruitment manage sections"', false);

        $this->get(route('apes-cic.recruitment.index'))
            ->assertRedirect(route('public.login'));
        $this->get(route('apes-cic.recruitment.applications.index'))
            ->assertRedirect(route('public.login'));
    }

    public function test_public_applicant_sees_recruitment_submenu_without_staff_manage(): void
    {
        $applicant = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_SERVICE_USER)
            ->create();

        $this->actingAs($applicant)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('>Recruitment</span>', false)
            ->assertDontSee('>Recruit manage</span>', false)
            ->assertDontSee('href="'.route('apes-cic.recruitment.index').'"', false);

        $this->actingAs($applicant)
            ->get(route('recruitment.index'))
            ->assertOk()
            ->assertSeeText('Open roles')
            ->assertSeeText('My applications')
            ->assertSee('aria-label="Recruitment sections"', false)
            ->assertDontSee('aria-label="Recruitment manage sections"', false);

        $this->actingAs($applicant)
            ->get(route('apes-cic.recruitment.index'))
            ->assertOk()
            ->assertDontSee('>Recruit manage</span>', false)
            ->assertDontSee('action="'.route('apes-cic.recruitment.store').'"', false);

        $this->actingAs($applicant)
            ->get(route('apes-cic.recruitment.applications.index'))
            ->assertForbidden();
    }

    public function test_staff_admin_and_superadmin_see_recruit_manage_with_roles_and_applications(): void
    {
        foreach ([
            AuthorizationProfile::ROLE_STAFF,
            AuthorizationProfile::ROLE_ADMINISTRATOR,
            AuthorizationProfile::ROLE_SUPER_ADMIN,
        ] as $roleName) {
            $actor = User::factory()
                ->protectedRole($roleName)
                ->create();

            $dashboard = $this->actingAs($actor)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee('>Recruit manage</span>', false)
                ->assertSee('href="'.route('apes-cic.recruitment.index').'"', false);

            if (app(ModuleSettingsService::class)->recruitmentPublicBoardEnabled()) {
                $dashboard->assertSee('>Recruitment</span>', false);
            }

            $this->actingAs($actor)
                ->get(route('apes-cic.recruitment.index'))
                ->assertOk()
                ->assertSee('aria-label="Recruitment manage sections"', false)
                ->assertSee('>Roles</a>', false)
                ->assertSee('>Applications</a>', false)
                ->assertSee('href="'.route('apes-cic.recruitment.applications.index').'"', false)
                ->assertSee('>Recruit manage</span>', false);

            $this->actingAs($actor)
                ->get(route('apes-cic.recruitment.applications.index'))
                ->assertOk()
                ->assertSee('aria-label="Recruitment manage sections"', false)
                ->assertSee('>Roles</a>', false)
                ->assertSee('>Applications</a>', false)
                ->assertDontSee('aria-label="Recruitment sections"', false)
                ->assertDontSeeText('My applications');
        }
    }

    public function test_hr_review_only_sees_applications_submenu_and_roles_crud_fail_closed(): void
    {
        $hr = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create();

        $this->stripStaffRecruitmentPermissionsExceptReview();

        $hr = $hr->fresh();
        $hr->unsetRelation('roles');
        $hr->unsetRelation('permissions');

        $role = RecruitmentRole::factory()->create();
        $application = RecruitmentApplication::factory()->create();

        $this->actingAs($hr)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('>Recruit manage</span>', false)
            ->assertSee('href="'.route('apes-cic.recruitment.applications.index').'"', false)
            ->assertDontSee('href="'.route('apes-cic.recruitment.index').'"', false);

        $this->actingAs($hr)
            ->get(route('apes-cic.recruitment.applications.index'))
            ->assertOk()
            ->assertSee('aria-label="Recruitment manage sections"', false)
            ->assertSee('>Applications</a>', false)
            ->assertDontSee('>Roles</a>', false)
            ->assertDontSeeText('My applications');

        $this->actingAs($hr)
            ->get(route('apes-cic.recruitment.index'))
            ->assertForbidden();

        $this->actingAs($hr)
            ->post(route('apes-cic.recruitment.store'), [
                'title' => 'HR should not create',
                'description' => 'Denied',
                'category' => 'staff',
            ])
            ->assertForbidden();

        $this->actingAs($hr)
            ->put(route('apes-cic.recruitment.update', $role), [
                'title' => 'HR should not update',
                'description' => 'Denied',
                'category' => 'staff',
            ])
            ->assertForbidden();

        $this->actingAs($hr)
            ->get(route('apes-cic.recruitment.applications.show', $application))
            ->assertOk();
    }

    public function test_staff_manage_primary_hidden_when_recruitment_module_disabled(): void
    {
        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create();

        ModuleInstallation::query()
            ->where('sub_core_key', 'apes-cic')
            ->where('module_key', 'recruitment')
            ->update(['enabled' => false]);

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('>Recruit manage</span>', false);

        $this->actingAs($staff)
            ->get('/apes-cic/recruitment')
            ->assertNotFound();
        $this->actingAs($staff)
            ->get('/apes-cic/recruitment/applications')
            ->assertNotFound();
    }

    public function test_apes_cic_hub_still_links_to_recruitment_manage(): void
    {
        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create();

        $html = $this->actingAs($staff)
            ->get('/apes-cic')
            ->assertOk()
            ->getContent();

        $listUrl = route('apes-cic.recruitment.index');
        $this->assertSame($listUrl.'#list', $this->hubActionHref($html, 'view', 'recruitment'));
        $this->assertSame($listUrl.'#create', $this->hubActionHref($html, 'create', 'recruitment'));
    }

    private function stripStaffRecruitmentPermissionsExceptReview(): void
    {
        $role = Role::query()
            ->where('guard_name', 'web')
            ->where('name', AuthorizationProfile::ROLE_STAFF)
            ->firstOrFail();

        $recruitmentPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->where('name', 'like', 'apes-cic.recruitment.%')
            ->where('name', '!=', RecruitmentApplication::PERMISSION_PREFIX.'review-applications')
            ->get();

        foreach ($recruitmentPermissions as $permission) {
            $role->revokePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function hubActionHref(string $html, string $action, string $moduleKey): string
    {
        $pattern = sprintf(
            '/<a[^>]*href="([^"]+)"[^>]*data-hub-action="%s"[^>]*data-module-key="%s"/',
            preg_quote($action, '/'),
            preg_quote($moduleKey, '/'),
        );

        $this->assertSame(
            1,
            preg_match($pattern, $html, $matches),
            "Missing hub {$action} link for {$moduleKey}.",
        );

        return html_entity_decode($matches[1], ENT_QUOTES);
    }
}
