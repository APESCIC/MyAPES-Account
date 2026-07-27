<?php

namespace Tests\Feature;

use App\Auth\OidcIdentity;
use App\Contracts\OidcIdentityProvider;
use App\Models\User;
use App\Services\LdapGroupResolver;
use App\Support\AccessCompatibilityDatabaseGuard;
use Database\Seeders\LocalQaSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Fakes\FakeLdapGroupResolver;
use Tests\Fakes\FakeOidcIdentityProvider;
use Tests\TestCase;

class RolelessApplicationCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_and_role_middleware_work_without_role_column(): void
    {
        $this->dropRoleColumn();

        $response = $this->post(route('public.register.submit'), [
            'name' => 'Public User',
            'email' => 'public@example.com',
            'password' => 'MyAPES-Test-Password-2026!',
            'password_confirmation' => 'MyAPES-Test-Password-2026!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $user = User::query()->sole();
        $this->assertSame(User::IDENTITY_LOCAL, $user->identity_type);
        $this->assertSame(User::ROLE_SERVICE_USER, $user->accessLevel());

        $admin = User::factory()->make(['email' => 'admin@example.com']);
        $admin->setAccessLevel(User::ROLE_ADMIN)->save();

        $this->actingAs($admin)->get(route('admin.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.index'))->assertForbidden();
    }

    public function test_oidc_creation_and_directory_access_work_without_role_column(): void
    {
        config([
            'myapes.roles.staff_groups' => ['position.staff'],
            'myapes.roles.admin_groups' => ['intranet.administrator'],
            'myapes.roles.superadmin_groups' => ['intranet.superadmin'],
        ]);

        $identityProvider = new FakeOidcIdentityProvider;
        $identityProvider->identity = new OidcIdentity(
            'roleless-subject',
            'roleless.staff@example.com',
            'Roleless Staff',
        );
        $directory = new FakeLdapGroupResolver;
        $directory->groups = ['position.staff'];
        $this->app->instance(OidcIdentityProvider::class, $identityProvider);
        $this->app->instance(LdapGroupResolver::class, $directory);

        $this->dropRoleColumn();

        $this->get(route('staff.auth.callback'))->assertRedirect(route('dashboard'));

        $user = User::query()->sole();
        $this->assertSame(User::IDENTITY_CLOUDRON_OIDC, $user->identity_type);
        $this->assertSame(User::ROLE_STAFF, $user->accessLevel());
        $this->actingAs($user)->get(route('apes-cic.tickets.index'))->assertOk();
    }

    public function test_local_qa_seeding_and_switching_work_without_role_column(): void
    {
        $this->dropRoleColumn();
        $this->seed(LocalQaSeeder::class);

        $response = $this->post(route('qa.switch-role'), [
            'role' => User::ROLE_ADMIN,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertSame(User::ROLE_ADMIN, auth()->user()?->accessLevel());
        $this->assertSame(LocalQaSeeder::ADMIN_EMAIL, auth()->user()?->email);
        $this->actingAs(auth()->user())->get(route('admin.index'))->assertOk();
    }

    private function dropRoleColumn(): void
    {
        app(AccessCompatibilityDatabaseGuard::class)->drop();

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
}
