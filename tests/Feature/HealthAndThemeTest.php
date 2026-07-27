<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthAndThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_reports_database_cache_and_development_release(): void
    {
        $this->get(route('health'))
            ->assertOk()
            ->assertExactJson([
                'status' => 'ok',
                'release' => 'development',
                'checks' => [
                    'database' => 'ok',
                    'cache' => 'ok',
                ],
            ]);
    }

    public function test_health_endpoint_returns_sanitized_service_unavailable_response(): void
    {
        $databaseConnection = config('database.default');
        $cacheStore = config('cache.default');

        try {
            config([
                'database.default' => 'missing-health-connection',
                'cache.default' => 'missing-health-store',
            ]);

            $response = $this->get(route('health'));
        } finally {
            config([
                'database.default' => $databaseConnection,
                'cache.default' => $cacheStore,
            ]);
        }

        $response
            ->assertServiceUnavailable()
            ->assertExactJson([
                'status' => 'unavailable',
                'release' => 'development',
                'checks' => [
                    'database' => 'failed',
                    'cache' => 'failed',
                ],
            ]);
    }

    public function test_light_theme_and_responsive_guest_sidebar_are_rendered(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('data-theme="light"', false);
        $response->assertSee("localStorage.getItem('myapes-theme')", false);
        $response->assertSee('data-theme-toggle', false);
        $response->assertSee('data-sidebar', false);
        $response->assertSee('data-sidebar-toggle', false);
        $response->assertSee('branding/logo-myapes-account.png', false);
        $response->assertSee('href="'.route('public.login').'"', false);
        $response->assertSee('href="'.route('public.register').'"', false);
        $response->assertSee('href="'.route('staff.login').'"', false);
        $response->assertDontSee('rel="mask-icon"', false);
    }

    public function test_authenticated_sidebar_is_role_aware(): void
    {
        $serviceUser = User::factory()->create([
            'role' => User::ROLE_SERVICE_USER,
        ]);

        $this->actingAs($serviceUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('aria-current="page"', false)
            ->assertSee('href="'.route('profile.edit').'"', false)
            ->assertSee('href="'.route('apes-cic.tickets.index').'"', false)
            ->assertSee('href="'.route('shelter.pets.index').'"', false)
            ->assertSee('href="'.route('petcare.pets.index').'"', false)
            ->assertDontSee('href="'.route('admin.index').'"', false);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('admin.index').'"', false);
    }

    public function test_generated_brand_assets_have_expected_dimensions(): void
    {
        $this->assertSame([1254, 1254], array_slice(getimagesize(resource_path('branding/source/apes-logo-v3.png')), 0, 2));
        $this->assertSame([1024, 1024], array_slice(getimagesize(public_path('branding/logo-myapes-account.png')), 0, 2));
        $this->assertSame([192, 192], array_slice(getimagesize(public_path('icons/pwa-maskable-192x192.png')), 0, 2));
        $this->assertSame([512, 512], array_slice(getimagesize(public_path('icons/pwa-maskable-512x512.png')), 0, 2));
        $this->assertSame([1200, 630], array_slice(getimagesize(public_path('social/og-image-1200x630.jpg')), 0, 2));
    }
}
