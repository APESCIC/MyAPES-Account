<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ModuleInstallationSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MascotHelperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ModuleInstallationSynchronizer::class)->synchronize();
    }

    public function test_guest_pages_render_spike_artwork_and_the_dismissible_dock(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('mascot/spike-welcome.png', false)
            ->assertSee('welcome-heading__mascot', false)
            ->assertDontSee('class="hero-image"', false)
            ->assertSee('mascot/spike-dock.png', false)
            ->assertSee('data-mascot-dock', false)
            ->assertSee('data-mascot-route="home"', false)
            ->assertSee('data-mascot-dismiss', false)
            ->assertSee('aria-label="Hide tip"', false)
            ->assertSeeText('Spike says')
            ->assertSeeText('Pick the door that matches you.')
            ->assertDontSee('bearded-dragon-natural.png', false);

        $this->get(route('public.login'))
            ->assertOk()
            ->assertSeeText('Use your public account.')
            ->assertSee('data-mascot-route="public.login"', false);

        $this->get(route('staff.login'))
            ->assertOk()
            ->assertSeeText('Staff use Cloudron.');
    }

    public function test_dashboard_uses_cartoon_spike_and_change_log_hides_the_dock(): void
    {
        $user = User::factory()->accessLevel(User::ROLE_SERVICE_USER)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('mascot/spike-welcome.png', false)
            ->assertSee('data-mascot-route="dashboard"', false)
            ->assertSeeText('Start with what needs you.');

        $this->get(route('change-log.index'))
            ->assertOk()
            ->assertDontSee('data-mascot-dock', false);
    }
}
