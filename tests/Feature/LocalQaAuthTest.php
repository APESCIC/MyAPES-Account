<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LocalQaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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
        $this->assertSame(User::ROLE_STAFF, auth()->user()?->accessLevel());
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
        $this->assertSame(User::ROLE_SERVICE_USER, auth()->user()?->accessLevel());
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
        $this->assertSame(User::ROLE_ADMIN, auth()->user()?->accessLevel());
        $this->assertSame(LocalQaSeeder::ADMIN_EMAIL, auth()->user()?->email);
    }

    public function test_public_qa_auto_login_does_not_create_remembered_authentication(): void
    {
        $this->seed(LocalQaSeeder::class);
        $recallerName = Auth::guard()->getRecallerName();

        $response = $this->get('/login');

        $response->assertCookieMissing($recallerName);
        $this->assertNull(
            User::query()
                ->where('email', LocalQaSeeder::SERVICE_USER_EMAIL)
                ->value('remember_token'),
        );
    }

    public function test_qa_role_switch_does_not_create_remembered_authentication(): void
    {
        $this->seed(LocalQaSeeder::class);
        $recallerName = Auth::guard()->getRecallerName();

        $response = $this->post(route('qa.switch-role'), [
            'role' => User::ROLE_STAFF,
        ]);

        $response->assertCookieMissing($recallerName);
        $this->assertNull(
            User::query()
                ->where('email', LocalQaSeeder::STAFF_EMAIL)
                ->value('remember_token'),
        );
    }

    public function test_local_qa_staff_login_ignores_remember_requests(): void
    {
        $this->seed(LocalQaSeeder::class);
        $recallerName = Auth::guard()->getRecallerName();

        $response = $this->post(route('staff.local-login.submit'), [
            'email' => LocalQaSeeder::STAFF_EMAIL,
            'password' => LocalQaSeeder::DEFAULT_PASSWORD,
            'remember' => '1',
        ]);

        $response->assertCookieMissing($recallerName);
        $this->assertNull(
            User::query()
                ->where('email', LocalQaSeeder::STAFF_EMAIL)
                ->value('remember_token'),
        );
    }

    public function test_public_qa_auto_login_rejects_a_suspended_seeded_user_before_authentication(): void
    {
        $this->seed(LocalQaSeeder::class);
        $user = User::query()
            ->where('email', LocalQaSeeder::SERVICE_USER_EMAIL)
            ->firstOrFail();
        $user->forceFill([
            'suspended_at' => now(),
            'suspension_reason' => 'QA account review',
        ])->save();

        $response = $this->get('/login');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.suspended_login_denied',
            'user_id' => $user->id,
        ]);
    }

    public function test_qa_role_switch_rejects_a_suspended_target_before_authentication(): void
    {
        $this->seed(LocalQaSeeder::class);
        $user = User::query()
            ->where('email', LocalQaSeeder::STAFF_EMAIL)
            ->firstOrFail();
        $user->forceFill([
            'suspended_at' => now(),
            'suspension_reason' => 'QA account review',
        ])->save();

        $response = $this->from('/')->post(route('qa.switch-role'), [
            'role' => User::ROLE_STAFF,
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('role');
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.suspended_login_denied',
            'user_id' => $user->id,
        ]);
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
