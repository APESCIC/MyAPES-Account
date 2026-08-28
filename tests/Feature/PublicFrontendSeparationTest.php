<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\PublicAuthController;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class PublicFrontendSeparationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function publicClassRouteProvider(): array
    {
        $staffAdminPaths = ['/admin', '/admin/users', '/admin/access', '/superadmin'];

        return [
            'public service-user' => [User::ROLE_SERVICE_USER, $staffAdminPaths],
            'volunteer' => [User::ROLE_VOLUNTEER, $staffAdminPaths],
            'student' => [User::ROLE_STUDENT, $staffAdminPaths],
        ];
    }

    #[DataProvider('publicClassRouteProvider')]
    public function test_public_volunteer_and_student_cannot_reach_staff_admin_or_super_admin_routes(
        string $accessLevel,
        array $paths,
    ): void {
        $user = User::factory()->accessLevel($accessLevel)->create();

        foreach ($paths as $path) {
            $this->actingAs($user)->get($path)->assertForbidden();
        }

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('href="'.route('admin.index').'"', false)
            ->assertDontSee('href="'.route('superadmin.index').'"', false);
    }

    public function test_guest_staff_login_shows_cloudron_and_local_qa_form_in_testing(): void
    {
        $this->get(route('staff.login'))
            ->assertOk()
            ->assertSee('Continue with APES Cloudron Login')
            ->assertSee('Local Staff Login');
    }

    public function test_production_staff_login_is_cloudron_only_and_rejects_local_password(): void
    {
        $this->app['env'] = 'production';

        $this->get(route('staff.login'))
            ->assertOk()
            ->assertSee('Continue with APES Cloudron Login')
            ->assertDontSee('Local Staff Login');

        $this->from(route('staff.login'))
            ->post(route('staff.local-login.submit'), [
                '_token' => csrf_token(),
                'email' => 'qa.staff@myapes.local',
                'password' => 'password',
            ])
            ->assertNotFound();
    }

    public function test_local_staff_login_aborts_outside_local_and_testing(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(NotFoundHttpException::class);

        $this->app->make(PublicAuthController::class)->localStaffLogin(
            Request::create('/staff/login', 'POST', [
                'email' => 'qa.staff@myapes.local',
                'password' => 'password',
            ]),
            $this->app->make(AuditLogger::class),
        );
    }

    public function test_public_login_remains_available_for_local_service_users(): void
    {
        $user = User::factory()->accessLevel(User::ROLE_SERVICE_USER)->create([
            'email' => 'public.local@example.test',
            'password' => 'public-password',
            'identity_type' => User::IDENTITY_LOCAL,
        ]);

        $this->post(route('public.login.submit'), [
            'email' => $user->email,
            'password' => 'public-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }
}
