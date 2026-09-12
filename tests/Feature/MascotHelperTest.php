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
            ->assertSee('data-mascot-toggle', false)
            ->assertSee('data-mascot-dismiss', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('aria-label="Show tip from Spike"', false)
            ->assertSee('aria-label="Hide tip"', false)
            ->assertSee('mascot-dock--collapsed', false)
            ->assertSee('has-mascot-dock', false)
            ->assertSee('id="mascot-dock-bubble"', false)
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
            ->assertSee('mascot-dock--collapsed', false)
            ->assertSee('has-mascot-dock', false)
            ->assertSeeText('Start with what needs you.');

        $this->get(route('change-log.index'))
            ->assertOk()
            ->assertDontSee('data-mascot-dock', false)
            ->assertDontSee('has-mascot-dock', false);
    }

    public function test_privacy_cookies_and_terms_hide_the_dock_and_help_keeps_a_tip(): void
    {
        foreach (['privacy', 'cookies', 'terms'] as $route) {
            $this->get(route($route))
                ->assertOk()
                ->assertDontSee('data-mascot-dock', false)
                ->assertDontSee('has-mascot-dock', false);
        }

        $this->get(route('help'))
            ->assertOk()
            ->assertSee('data-mascot-dock', false)
            ->assertSee('data-mascot-route="help"', false)
            ->assertSeeText('Look here first.');
    }

    public function test_ticket_and_pet_create_pages_keep_a_collapsed_dock_with_clearance(): void
    {
        $user = User::factory()->accessLevel(User::ROLE_SERVICE_USER)->create();

        $this->actingAs($user)
            ->get(route('apes-cic.tickets.index'))
            ->assertOk()
            ->assertSee('id="priority"', false)
            ->assertSee('data-mascot-dock', false)
            ->assertSee('data-mascot-route="apes-cic.tickets.index"', false)
            ->assertSee('mascot-dock--collapsed', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('has-mascot-dock', false)
            ->assertSeeText('Describe the need clearly.');

        $this->actingAs($user)
            ->get(route('shelter.pets.index'))
            ->assertOk()
            ->assertSee('id="pet_health_issues"', false)
            ->assertSee('data-mascot-dock', false)
            ->assertSee('mascot-dock--collapsed', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('has-mascot-dock', false);
    }
}
