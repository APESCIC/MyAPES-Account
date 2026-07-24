<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LocalQaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalQaAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_login_auto_signs_in_seeded_service_user_in_testing(): void
    {
        $this->seed(LocalQaSeeder::class);

        $response = $this->get('/login');

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertAuthenticatedAs(
            User::query()->where('email', LocalQaSeeder::SERVICE_USER_EMAIL)->firstOrFail()
        );
    }

    public function test_role_switcher_can_switch_to_seeded_staff_user(): void
    {
        $this->seed(LocalQaSeeder::class);

        $response = $this->post(route('qa.switch-role'), [
            'role' => User::ROLE_STAFF,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertSame(User::ROLE_STAFF, auth()->user()?->role);
        $this->assertSame(LocalQaSeeder::STAFF_EMAIL, auth()->user()?->email);
    }

    public function test_role_switcher_can_switch_to_seeded_public_user(): void
    {
        $this->seed(LocalQaSeeder::class);

        $response = $this->post(route('qa.switch-role'), [
            'role' => User::ROLE_SERVICE_USER,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertSame(User::ROLE_SERVICE_USER, auth()->user()?->role);
        $this->assertSame(LocalQaSeeder::SERVICE_USER_EMAIL, auth()->user()?->email);
    }

    public function test_role_switcher_can_switch_to_seeded_admin_user(): void
    {
        $this->seed(LocalQaSeeder::class);

        $response = $this->post(route('qa.switch-role'), [
            'role' => User::ROLE_ADMIN,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertSame(User::ROLE_ADMIN, auth()->user()?->role);
        $this->assertSame(LocalQaSeeder::ADMIN_EMAIL, auth()->user()?->email);
    }

    public function test_role_switcher_rejects_unsupported_role(): void
    {
        $this->seed(LocalQaSeeder::class);

        $response = $this->from('/')->post(route('qa.switch-role'), [
            'role' => 'superadmin',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('role');
        $this->assertGuest();
    }

    public function test_role_switcher_is_not_available_in_production(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        $csrfToken = str_repeat('a', 40);

        $this
            ->withSession(['_token' => $csrfToken])
            ->post(route('qa.switch-role'), [
                '_token' => $csrfToken,
                'role' => User::ROLE_STAFF,
            ])
            ->assertNotFound();

        $this->assertGuest();
    }
}
