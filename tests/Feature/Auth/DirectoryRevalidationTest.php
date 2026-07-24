<?php

namespace Tests\Feature\Auth;

use App\Exceptions\DirectoryIdentityNotFound;
use App\Exceptions\DirectoryUnavailable;
use App\Http\Middleware\RevalidateDirectoryAccess;
use App\Models\User;
use App\Services\LdapGroupResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeLdapGroupResolver;
use Tests\TestCase;

class DirectoryRevalidationTest extends TestCase
{
    use RefreshDatabase;

    private FakeLdapGroupResolver $directory;

    private CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'myapes.directory.revalidate_seconds' => 300,
            'myapes.directory.revalidate_in_local' => true,
            'myapes.roles.staff_groups' => [
                'position.staff',
                'position.students',
                'position.volunteers',
            ],
            'myapes.roles.admin_groups' => ['intranet.administrator'],
            'myapes.roles.superadmin_groups' => ['intranet.superadmin'],
        ]);

        $this->directory = new FakeLdapGroupResolver;
        $this->app->instance(LdapGroupResolver::class, $this->directory);

        $this->now = CarbonImmutable::parse('2026-07-24 16:00:00', 'UTC');
        $this->travelTo($this->now);
    }

    public function test_directory_is_not_queried_before_five_minutes_have_elapsed(): void
    {
        $user = $this->directoryUser(User::ROLE_STAFF, ['position.staff']);

        $response = $this
            ->actingAs($user)
            ->withSession([
                RevalidateDirectoryAccess::SESSION_KEY => $this->now->subSeconds(299)->timestamp,
            ])
            ->get(route('dashboard'));

        $response->assertOk();
        $this->assertSame([], $this->directory->resolvedEmails);
    }

    public function test_role_is_demoted_before_authorization_on_the_due_request(): void
    {
        $user = $this->directoryUser(User::ROLE_ADMIN, ['intranet.administrator']);
        $this->directory->groups = ['position.staff'];

        $response = $this
            ->actingAs($user)
            ->withSession([
                RevalidateDirectoryAccess::SESSION_KEY => $this->now->subSeconds(300)->timestamp,
            ])
            ->get(route('admin.index'));

        $response->assertForbidden();
        $this->assertSame([$user->email], $this->directory->resolvedEmails);

        $user->refresh();
        $this->assertSame(User::ROLE_STAFF, $user->role);
        $this->assertSame(['position.staff'], $user->ldap_groups);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.directory_role_changed',
            'user_id' => $user->id,
        ]);
    }

    public function test_role_promotion_applies_to_the_same_request(): void
    {
        $user = $this->directoryUser(User::ROLE_STAFF, ['position.staff']);
        $this->directory->groups = ['intranet.administrator'];

        $response = $this
            ->actingAs($user)
            ->withSession([
                RevalidateDirectoryAccess::SESSION_KEY => $this->now->subSeconds(300)->timestamp,
            ])
            ->get(route('admin.index'));

        $response->assertOk();
        $this->assertSame(User::ROLE_ADMIN, $user->fresh()->role);
        $response->assertSessionHas(
            RevalidateDirectoryAccess::SESSION_KEY,
            $this->now->timestamp,
        );
    }

    public function test_directory_revocation_downgrades_and_logs_out_the_user(): void
    {
        $user = $this->directoryUser(User::ROLE_STAFF, ['position.staff']);
        $this->directory->failure = new DirectoryIdentityNotFound('not found');

        $response = $this
            ->actingAs($user)
            ->withSession([
                RevalidateDirectoryAccess::SESSION_KEY => $this->now->subSeconds(300)->timestamp,
            ])
            ->get(route('dashboard'));

        $response->assertRedirect(route('staff.login'));
        $response->assertSessionHasErrors('staff');
        $this->assertGuest();

        $user->refresh();
        $this->assertSame(User::ROLE_SERVICE_USER, $user->role);
        $this->assertSame([], $user->ldap_groups);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.directory_access_revoked',
            'user_id' => $user->id,
        ]);
    }

    public function test_directory_outage_fails_closed_without_changing_stored_access(): void
    {
        $user = $this->directoryUser(User::ROLE_ADMIN, ['intranet.administrator']);
        $validatedAt = $this->now->subSeconds(300)->timestamp;
        $this->directory->failure = new DirectoryUnavailable('sensitive connection details');

        $response = $this
            ->actingAs($user)
            ->withSession([
                RevalidateDirectoryAccess::SESSION_KEY => $validatedAt,
            ])
            ->get(route('admin.index'));

        $response->assertStatus(503);
        $response->assertDontSee('sensitive connection details');
        $response->assertSessionHas(RevalidateDirectoryAccess::SESSION_KEY, $validatedAt);
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertSame(User::ROLE_ADMIN, $user->role);
        $this->assertSame(['intranet.administrator'], $user->ldap_groups);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.directory_unavailable',
            'user_id' => $user->id,
        ]);
    }

    public function test_public_users_and_default_local_qa_requests_bypass_directory_revalidation(): void
    {
        $publicUser = User::factory()->create([
            'oidc_sub' => null,
            'role' => User::ROLE_SERVICE_USER,
            'ldap_groups' => [],
        ]);

        $this->actingAs($publicUser)->get(route('dashboard'))->assertOk();

        config(['myapes.directory.revalidate_in_local' => false]);
        $qaStaff = $this->directoryUser(User::ROLE_STAFF, ['position.staff']);

        $this
            ->actingAs($qaStaff)
            ->withSession([RevalidateDirectoryAccess::SESSION_KEY => 0])
            ->get(route('dashboard'))
            ->assertOk();

        $this->assertSame([], $this->directory->resolvedEmails);
    }

    /**
     * @param  array<int, string>  $groups
     */
    private function directoryUser(string $role, array $groups): User
    {
        return User::factory()->create([
            'oidc_sub' => 'cloudron-'.str()->uuid(),
            'role' => $role,
            'ldap_groups' => $groups,
        ]);
    }
}
