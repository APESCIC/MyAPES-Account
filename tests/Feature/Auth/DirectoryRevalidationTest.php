<?php

namespace Tests\Feature\Auth;

use App\Exceptions\DirectoryIdentityNotFound;
use App\Exceptions\DirectoryUnavailable;
use App\Http\Middleware\RevalidateDirectoryAccess;
use App\Models\DirectorySyncRun;
use App\Models\User;
use App\Services\LdapGroupResolver;
use App\Services\SessionAuthorizationContext;
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
        $user = $this->directoryUser(User::ROLE_STAFF, ['myapesaccount.staff']);

        $response = $this
            ->actingAs($user)
            ->withSession($this->oidcContext(
                $user,
                $this->now->subSeconds(299)->timestamp,
            ))
            ->get(route('dashboard'));

        $response->assertOk();
        $this->assertSame([], $this->directory->resolvedEmails);
    }

    public function test_completed_catalogue_failure_immediately_stales_a_current_oidc_session(): void
    {
        $user = $this->directoryUser(User::ROLE_ADMIN, ['myapesaccount.admin']);
        $context = $this->oidcContext(
            $user,
            $this->now->subSeconds(299)->timestamp,
        );
        $failedRun = DirectorySyncRun::query()->create([
            'source' => DirectorySyncRun::SOURCE_SCHEDULED,
            'status' => DirectorySyncRun::STATUS_FAILED,
            'started_at' => $this->now,
            'finished_at' => $this->now,
            'groups_seen' => 0,
            'groups_missing' => 0,
            'error_code' => 'directory_unavailable',
        ]);
        $this->directory->failure = new DirectoryUnavailable(
            'sensitive connection details',
        );

        $response = $this
            ->actingAs($user)
            ->withSession($context)
            ->get(route('admin.index'));

        $response->assertStatus(503);
        $response->assertDontSee('sensitive connection details');
        $this->assertSame([$user->email], $this->directory->resolvedEmails);
        $response->assertSessionHas(
            SessionAuthorizationContext::DIRECTORY_GENERATION_KEY,
            0,
        );
        $this->assertAuthenticatedAs($user);

        $this->directory->failure = null;
        $this->directory->groups = ['myapesaccount.admin'];
        $recovered = $this
            ->actingAs($user)
            ->withSession($context)
            ->get(route('admin.index'));

        $recovered->assertOk();
        $recovered->assertSessionHas(
            SessionAuthorizationContext::DIRECTORY_GENERATION_KEY,
            $failedRun->id,
        );
    }

    public function test_role_is_demoted_before_authorization_on_the_due_request(): void
    {
        $user = $this->directoryUser(User::ROLE_ADMIN, ['myapesaccount.admin']);
        $this->directory->groups = ['myapesaccount.staff'];

        $response = $this
            ->actingAs($user)
            ->withSession($this->oidcContext(
                $user,
                $this->now->subSeconds(300)->timestamp,
            ))
            ->get(route('admin.index'));

        $response->assertForbidden();
        $this->assertSame([$user->email], $this->directory->resolvedEmails);

        $user->refresh();
        $this->assertSame(User::ROLE_STAFF, $user->accessLevel());
        $this->assertSame(['myapesaccount.staff'], $user->ldap_groups);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.directory_role_changed',
            'user_id' => $user->id,
        ]);
    }

    public function test_role_promotion_applies_to_the_same_request(): void
    {
        $user = $this->directoryUser(User::ROLE_STAFF, ['myapesaccount.staff']);
        $this->directory->groups = ['myapesaccount.admin'];

        $response = $this
            ->actingAs($user)
            ->withSession($this->oidcContext(
                $user,
                $this->now->subSeconds(300)->timestamp,
            ))
            ->get(route('admin.index'));

        $response->assertOk();
        $this->assertSame(User::ROLE_ADMIN, $user->fresh()->accessLevel());
        $response->assertSessionHas(
            RevalidateDirectoryAccess::SESSION_KEY,
            $this->now->timestamp,
        );
    }

    public function test_due_revalidation_uses_persisted_mappings_and_rotates_the_epoch(): void
    {
        $user = $this->directoryUser(User::ROLE_STAFF, ['myapesaccount.staff']);
        $user->refresh();
        $originalRememberToken = $user->remember_token;
        $this->directory->groups = ['myapesaccount.admin'];

        $response = $this
            ->actingAs($user)
            ->withSession($this->oidcContext(
                $user,
                $this->now->subSeconds(300)->timestamp,
            ))
            ->get(route('admin.index'));

        $response->assertOk();
        $user->refresh();
        $this->assertSame(2, $user->authorization_epoch);
        $this->assertNotSame($originalRememberToken, $user->remember_token);
        $this->assertSame(
            ['administrator'],
            $user->roles()->pluck('name')->all(),
        );
        $response->assertSessionHas('myapes.authorization_epoch', 2);
        $response->assertSessionHas(
            RevalidateDirectoryAccess::SESSION_KEY,
            $this->now->timestamp,
        );
    }

    public function test_directory_revocation_downgrades_and_logs_out_the_user(): void
    {
        $user = $this->directoryUser(User::ROLE_STAFF, ['myapesaccount.staff']);
        $this->directory->failure = new DirectoryIdentityNotFound('not found');

        $response = $this
            ->actingAs($user)
            ->withSession($this->oidcContext(
                $user,
                $this->now->subSeconds(300)->timestamp,
            ))
            ->get(route('dashboard'));

        $response->assertRedirect(route('staff.login'));
        $response->assertSessionHasErrors('staff');
        $this->assertGuest();

        $user->refresh();
        $this->assertSame(User::ROLE_SERVICE_USER, $user->accessLevel());
        $this->assertSame([], $user->ldap_groups);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.directory_access_revoked',
            'user_id' => $user->id,
        ]);
    }

    public function test_directory_revocation_returns_unauthorized_for_json_requests(): void
    {
        $user = $this->directoryUser(User::ROLE_STAFF, ['myapesaccount.staff']);
        $this->directory->failure = new DirectoryIdentityNotFound('not found');

        $response = $this
            ->actingAs($user)
            ->withSession($this->oidcContext(
                $user,
                $this->now->subSeconds(300)->timestamp,
            ))
            ->getJson(route('dashboard'));

        $this->assertSame(401, $response->getStatusCode());
        $response->assertExactJson(['message' => 'Unauthenticated.']);
        $this->assertGuest();
        $this->assertSame(
            User::ROLE_SERVICE_USER,
            $user->fresh()->accessLevel(),
        );
    }

    public function test_directory_outage_fails_closed_without_changing_stored_access(): void
    {
        $user = $this->directoryUser(User::ROLE_ADMIN, ['myapesaccount.admin']);
        $validatedAt = $this->now->subSeconds(300)->timestamp;
        $this->directory->failure = new DirectoryUnavailable('sensitive connection details');

        $response = $this
            ->actingAs($user)
            ->withSession($this->oidcContext($user, $validatedAt))
            ->get(route('admin.index'));

        $response->assertStatus(503);
        $response->assertDontSee('sensitive connection details');
        $response->assertSessionHas(RevalidateDirectoryAccess::SESSION_KEY, $validatedAt);
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertSame(User::ROLE_ADMIN, $user->accessLevel());
        $this->assertSame(['myapesaccount.admin'], $user->ldap_groups);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.directory_unavailable',
            'user_id' => $user->id,
        ]);
    }

    public function test_public_users_and_default_local_qa_requests_bypass_directory_revalidation(): void
    {
        $publicUser = User::factory()
            ->accessLevel(User::ROLE_SERVICE_USER)
            ->create([
                'oidc_sub' => null,
                'identity_type' => User::IDENTITY_LOCAL,
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
        return User::factory()
            ->accessLevel($role)
            ->cloudronIdentity('cloudron-'.str()->uuid())
            ->create(['ldap_groups' => $groups]);
    }

    /**
     * @return array<string, int|string>
     */
    private function oidcContext(User $user, int $validatedAt): array
    {
        $user->refresh();

        return [
            'myapes.authentication_method' => 'cloudron_oidc',
            'myapes.authorization_epoch' => $user->authorization_epoch,
            RevalidateDirectoryAccess::SESSION_KEY => $validatedAt,
            SessionAuthorizationContext::DIRECTORY_GENERATION_KEY => (int) (
                DirectorySyncRun::query()
                    ->whereIn('status', [
                        DirectorySyncRun::STATUS_SUCCEEDED,
                        DirectorySyncRun::STATUS_FAILED,
                    ])
                    ->max('id') ?? 0
            ),
        ];
    }
}
