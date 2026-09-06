<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandedErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_url_renders_branded_404(): void
    {
        $this->get('/this-route-does-not-exist-for-branded-404')
            ->assertNotFound()
            ->assertSeeText('MyAPES Core')
            ->assertSeeText('Page not found')
            ->assertSeeText('That address is not available in MyAPES Core')
            ->assertSeeText('Back to home')
            ->assertDontSee('404 | Not Found', false);
    }

    public function test_public_user_denied_admin_routes_render_branded_403(): void
    {
        $user = User::factory()->accessLevel(User::ROLE_SERVICE_USER)->create();

        foreach (['/admin', '/superadmin'] as $path) {
            $this->actingAs($user)
                ->get($path)
                ->assertForbidden()
                ->assertSeeText('MyAPES Core')
                ->assertSeeText('Access denied')
                ->assertSeeText('You do not have permission to view this page')
                ->assertSeeText('Go to dashboard')
                ->assertDontSee('403 | Forbidden', false);
        }
    }
}
