<?php

namespace Tests\Feature;

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

    public function test_light_theme_is_default_and_theme_controls_are_rendered(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('data-theme="light"', false);
        $response->assertSee("localStorage.getItem('myapes-theme')", false);
        $response->assertSee('data-theme-toggle', false);
        $response->assertSee('logos/myapes-header-light.svg', false);
        $response->assertSee('logos/myapes-header-dark.svg', false);
    }
}
