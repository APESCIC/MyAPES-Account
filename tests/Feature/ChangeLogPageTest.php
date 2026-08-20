<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ChangeLogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_read_the_complete_progressive_change_log(): void
    {
        $response = $this->get('/change-log');

        $response
            ->assertOk()
            ->assertSeeText('Change Log Hub')
            ->assertSeeText('Current version v0.21.0')
            ->assertSee('data-change-log', false)
            ->assertSee('data-change-log-controls hidden', false)
            ->assertSee('href="#release-v0-21-0"', false)
            ->assertSee('<details', false)
            ->assertSeeText('Enable or disable Cloudron groups for app access')
            ->assertSeeText('Admin Super Admin panel and overview chart fix')
            ->assertSeeText('Polish Spike tip dock dismiss and avatar')
            ->assertSeeText('Group dashboard service totals by subcore panels')
            ->assertSeeText('Fix release history tests for v0.19.1')
            ->assertSeeText('Clearer Admin Modules registry layout')
            ->assertSeeText('Agent release metadata prepare command')
            ->assertSeeText('Fix sub-core hub Recent activity layout')
            ->assertSeeText('Remediate development nanoid advisory')
            ->assertSeeText('Admin operational analytics dashboard')
            ->assertSeeText('Separate public and staff profiles')
            ->assertSeeText('MySQL-only runtime and Cloudron Redis')
            ->assertSeeText('Cloudron deploy without Actions artifacts')
            ->assertSeeText('Desert theme and Spike helper')
            ->assertSeeText('APES Pet Care Clinic modules')
            ->assertSeeText('Repository discovery and support metadata')
            ->assertSeeText('APES CIC Tickets and Cases');

        $this->assertSame(41, substr_count($response->getContent(), 'data-release-record'));

        foreach (['0.21.0', '0.20.0', '0.19.4', '0.19.3', '0.19.2', '0.19.1', '0.19.0', '0.18.2', '0.18.1', '0.18.0', '0.17.0', '0.16.3', '0.16.1', '0.16.0', '0.15.0', '0.14.0', '0.13.1', '0.13.0', '0.12.1', '0.12.0', '0.11.0', '0.10.0', '0.9.2', '0.9.1', '0.9.0', '0.8.3', '0.8.2', '0.8.1', '0.8.0', '0.7.1', '0.7.0', '0.6.1', '0.6.0', '0.5.0', '0.4.2', '0.4.1', '0.4.0', '0.3.0', '0.2.1', '0.2.0', '0.1.0'] as $version) {
            $response->assertSeeText("v{$version}");
        }

        $this->assertProgressiveDetailsContainReleaseContent($response);
    }

    public function test_public_and_staff_accounts_can_read_the_change_log(): void
    {
        foreach ([User::ROLE_SERVICE_USER, User::ROLE_STAFF] as $role) {
            $user = User::factory()->accessLevel($role)->create();

            $this->actingAs($user)
                ->get('/change-log')
                ->assertOk()
                ->assertSeeText('Current version v0.21.0');

            $this->post(route('auth.logout'));
        }
    }

    public function test_shared_layout_exposes_the_accessible_current_version_link(): void
    {
        foreach (['/', '/register', '/staff/login', '/change-log'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('href="'.route('change-log.index').'"', false)
                ->assertSee('aria-label="View the MyAPES Account change log for version v0.21.0"', false)
                ->assertSeeText('v0.21.0');
        }

        $this->view('auth.public-login')
            ->assertSee('href="'.route('change-log.index').'"', false)
            ->assertSeeText('v0.21.0');

        $user = User::factory()->accessLevel(User::ROLE_SERVICE_USER)->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('href="'.route('change-log.index').'"', false)
            ->assertSeeText('v0.21.0');
    }

    private function assertProgressiveDetailsContainReleaseContent(TestResponse $response): void
    {
        $content = $response->getContent();
        $firstDetails = strpos($content, '<details');
        $firstChange = strpos($content, 'Added an app allowlist on directory groups with Enable and Disable controls in Admin Groups.');

        $this->assertNotFalse($firstDetails);
        $this->assertNotFalse($firstChange);
        $this->assertGreaterThan($firstDetails, $firstChange);
    }
}
