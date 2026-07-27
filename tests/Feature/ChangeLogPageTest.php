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
            ->assertSeeText('Current version v0.6.1')
            ->assertSee('data-change-log', false)
            ->assertSee('data-change-log-controls hidden', false)
            ->assertSee('href="#release-v0-6-1"', false)
            ->assertSee('<details', false)
            ->assertSeeText('Post-merge release validation correction');

        $this->assertSame(10, substr_count($response->getContent(), 'data-release-record'));

        foreach (['0.6.1', '0.6.0', '0.5.0', '0.4.2', '0.4.1', '0.4.0', '0.3.0', '0.2.1', '0.2.0', '0.1.0'] as $version) {
            $response->assertSeeText("v{$version}");
        }

        $this->assertProgressiveDetailsContainReleaseContent($response);
    }

    public function test_public_and_staff_accounts_can_read_the_change_log(): void
    {
        foreach ([User::ROLE_SERVICE_USER, User::ROLE_STAFF] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get('/change-log')
                ->assertOk()
                ->assertSeeText('Current version v0.6.1');

            $this->post(route('auth.logout'));
        }
    }

    public function test_shared_layout_exposes_the_accessible_current_version_link(): void
    {
        foreach (['/', '/register', '/staff/login', '/change-log'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('href="'.route('change-log.index').'"', false)
                ->assertSee('aria-label="View the MyAPES Account change log for version v0.6.1"', false)
                ->assertSeeText('v0.6.1');
        }

        $this->view('auth.public-login')
            ->assertSee('href="'.route('change-log.index').'"', false)
            ->assertSeeText('v0.6.1');

        $user = User::factory()->create(['role' => User::ROLE_SERVICE_USER]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('href="'.route('change-log.index').'"', false)
            ->assertSeeText('v0.6.1');
    }

    private function assertProgressiveDetailsContainReleaseContent(TestResponse $response): void
    {
        $content = $response->getContent();
        $firstDetails = strpos($content, '<details');
        $firstChange = strpos($content, 'Added the public Change Log Hub');

        $this->assertNotFalse($firstDetails);
        $this->assertNotFalse($firstChange);
        $this->assertGreaterThan($firstDetails, $firstChange);
    }
}
