<?php

namespace Tests\Feature;

use App\Models\ModuleInstallation;
use App\Models\RecruitmentRole;
use App\Models\User;
use App\Services\AuthorizationProfile;
use App\Services\ModuleInstallationSynchronizer;
use Database\Seeders\LocalQaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRecruitmentBoardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ModuleInstallationSynchronizer::class)->synchronize();
    }

    public function test_guest_sees_only_open_roles_across_categories(): void
    {
        $staff = User::factory()->create();

        $openStaff = RecruitmentRole::factory()
            ->open()
            ->category('staff')
            ->create([
                'created_by' => $staff->id,
                'title' => 'Open staff role',
                'summary' => 'Staff summary visible',
            ]);
        $openVolunteer = RecruitmentRole::factory()
            ->open()
            ->category('volunteer')
            ->create([
                'created_by' => $staff->id,
                'title' => 'Open volunteer role',
            ]);
        $openStudent = RecruitmentRole::factory()
            ->open()
            ->category('student')
            ->create([
                'created_by' => $staff->id,
                'title' => 'Open student role',
            ]);
        $draft = RecruitmentRole::factory()
            ->draft()
            ->category('staff')
            ->create([
                'created_by' => $staff->id,
                'title' => 'Secret draft role',
                'summary' => 'Draft must never leak',
                'description' => 'Draft description must never leak',
            ]);
        $closed = RecruitmentRole::factory()
            ->closed()
            ->category('volunteer')
            ->create([
                'created_by' => $staff->id,
                'title' => 'Secret closed role',
                'summary' => 'Closed must never leak',
                'description' => 'Closed description must never leak',
            ]);

        $response = $this->get(route('recruitment.index'));

        $response
            ->assertOk()
            ->assertSeeText('Open roles')
            ->assertSeeText($openStaff->title)
            ->assertSeeText($openVolunteer->title)
            ->assertSeeText($openStudent->title)
            ->assertSeeText('Staff summary visible')
            ->assertDontSeeText($draft->title)
            ->assertDontSeeText($closed->title)
            ->assertDontSeeText('Draft must never leak')
            ->assertDontSeeText('Closed must never leak')
            ->assertDontSeeText('Draft description must never leak')
            ->assertDontSeeText('Closed description must never leak')
            ->assertSee('href="'.route('recruitment.show', $openStaff).'"', false);
    }

    public function test_guest_can_filter_roles_by_category(): void
    {
        $staff = User::factory()->create();

        RecruitmentRole::factory()
            ->open()
            ->category('staff')
            ->create([
                'created_by' => $staff->id,
                'title' => 'Filter staff role',
            ]);
        RecruitmentRole::factory()
            ->open()
            ->category('volunteer')
            ->create([
                'created_by' => $staff->id,
                'title' => 'Filter volunteer role',
            ]);
        RecruitmentRole::factory()
            ->open()
            ->category('student')
            ->create([
                'created_by' => $staff->id,
                'title' => 'Filter student role',
            ]);

        $this->get(route('recruitment.index', ['category' => 'volunteer']))
            ->assertOk()
            ->assertSeeText('Filter volunteer role')
            ->assertDontSeeText('Filter staff role')
            ->assertDontSeeText('Filter student role')
            ->assertSee('aria-current="page"', false);

        $this->get(route('recruitment.index', ['category' => 'unknown']))
            ->assertNotFound();
    }

    public function test_guest_can_view_open_role_detail_but_not_draft_or_closed(): void
    {
        $staff = User::factory()->create();

        $open = RecruitmentRole::factory()
            ->open()
            ->category('student')
            ->create([
                'created_by' => $staff->id,
                'title' => 'Visible student opportunity',
                'summary' => 'Public summary',
                'description' => "Full public description\nwith a second line.",
                'location' => 'London',
                'commitment' => '2 days per week',
            ]);
        $draft = RecruitmentRole::factory()
            ->draft()
            ->create([
                'created_by' => $staff->id,
                'title' => 'Hidden draft opportunity',
                'description' => 'Draft body must not leak',
            ]);
        $closed = RecruitmentRole::factory()
            ->closed()
            ->create([
                'created_by' => $staff->id,
                'title' => 'Hidden closed opportunity',
                'description' => 'Closed body must not leak',
            ]);

        $this->get(route('recruitment.show', $open))
            ->assertOk()
            ->assertSeeText('Visible student opportunity')
            ->assertSeeText('Public summary')
            ->assertSeeText('Full public description')
            ->assertSeeText('Location: London')
            ->assertSeeText('Commitment: 2 days per week')
            ->assertSee('href="'.route('recruitment.index').'"', false)
            ->assertDontSeeText('Hidden draft opportunity')
            ->assertDontSee('Publish / open');

        $this->get(route('recruitment.show', $draft))
            ->assertNotFound()
            ->assertDontSeeText('Hidden draft opportunity')
            ->assertDontSeeText('Draft body must not leak');

        $this->get(route('recruitment.show', $closed))
            ->assertNotFound()
            ->assertDontSeeText('Hidden closed opportunity')
            ->assertDontSeeText('Closed body must not leak');

        $this->get('/recruitment/999999')
            ->assertNotFound();
    }

    public function test_public_chrome_links_to_recruitment_board_when_module_enabled(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="'.route('recruitment.index').'"', false)
            ->assertSeeText('Open roles')
            ->assertSeeText('View open roles')
            ->assertSeeText('Recruitment')
            ->assertDontSee('>Roles</span>', false);

        $board = $this->get(route('recruitment.index'))
            ->assertOk()
            ->assertSee('href="'.route('recruitment.index').'"', false)
            ->assertSeeText('Recruitment')
            ->assertSeeText('Open roles')
            ->assertDontSeeText('My applications');

        $board->assertSee('aria-label="Recruitment sections"', false);
    }

    public function test_signed_in_public_user_sees_recruitment_submenu_with_my_applications(): void
    {
        $applicant = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_SERVICE_USER)
            ->create();

        $this->actingAs($applicant)
            ->get(route('recruitment.index'))
            ->assertOk()
            ->assertSeeText('Recruitment')
            ->assertSeeText('Open roles')
            ->assertSeeText('My applications')
            ->assertSee('href="'.route('recruitment.applications.index').'"', false)
            ->assertDontSee('href="'.route('apes-cic.recruitment.index').'"', false)
            ->assertDontSee('href="'.route('apes-cic.recruitment.applications.index').'"', false);

        $this->actingAs($applicant)
            ->get(route('recruitment.applications.index'))
            ->assertOk()
            ->assertSeeText('My applications')
            ->assertSee('aria-current="page"', false)
            ->assertSee('href="'.route('recruitment.index').'"', false);
    }

    public function test_guest_applications_route_requires_login_without_staff_manage_bleed(): void
    {
        $this->get(route('recruitment.applications.index'))
            ->assertRedirect();

        $this->get(route('recruitment.index'))
            ->assertOk()
            ->assertDontSeeText('My applications')
            ->assertDontSee('href="'.route('apes-cic.recruitment.applications.index').'"', false);
    }

    public function test_public_roles_board_is_unavailable_when_module_disabled(): void
    {
        ModuleInstallation::query()
            ->where('sub_core_key', 'apes-cic')
            ->where('module_key', 'recruitment')
            ->update(['enabled' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSeeText('View open roles')
            ->assertDontSee('href="'.route('recruitment.index').'"', false);

        $this->get('/recruitment')->assertNotFound();
    }

    public function test_local_qa_seeder_creates_demo_open_roles_in_each_category(): void
    {
        $this->seed(LocalQaSeeder::class);

        $this->assertTrue(
            RecruitmentRole::query()
                ->open()
                ->where('category', 'staff')
                ->where('title', 'QA Seed: Operations coordinator')
                ->exists(),
        );
        $this->assertTrue(
            RecruitmentRole::query()
                ->open()
                ->where('category', 'volunteer')
                ->where('title', 'QA Seed: Community outreach volunteer')
                ->exists(),
        );
        $this->assertTrue(
            RecruitmentRole::query()
                ->open()
                ->where('category', 'student')
                ->where('title', 'QA Seed: Placement student — administration')
                ->exists(),
        );
        $this->assertTrue(
            RecruitmentRole::query()
                ->where('status', RecruitmentRole::STATUS_DRAFT)
                ->where('title', 'QA Seed: Draft communications lead')
                ->exists(),
        );
        $this->assertTrue(
            RecruitmentRole::query()
                ->where('status', RecruitmentRole::STATUS_CLOSED)
                ->where('title', 'QA Seed: Closed weekend volunteer')
                ->exists(),
        );

        $this->get(route('recruitment.index'))
            ->assertOk()
            ->assertSeeText('QA Seed: Operations coordinator')
            ->assertSeeText('QA Seed: Community outreach volunteer')
            ->assertSeeText('QA Seed: Placement student — administration')
            ->assertDontSeeText('QA Seed: Draft communications lead')
            ->assertDontSeeText('QA Seed: Closed weekend volunteer');
    }
}
