<?php

namespace Tests\Feature\Auth;

use App\Auth\OidcIdentity;
use App\Contracts\OidcIdentityProvider;
use App\Exceptions\DirectoryIdentityNotFound;
use App\Exceptions\DirectoryUnavailable;
use App\Http\Middleware\RevalidateDirectoryAccess;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\LdapGroupResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeLdapGroupResolver;
use Tests\Fakes\FakeOidcIdentityProvider;
use Tests\TestCase;

class StaffOidcAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private FakeOidcIdentityProvider $identityProvider;

    private FakeLdapGroupResolver $directory;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'myapes.roles.staff_groups' => [
                'position.staff',
                'position.students',
                'position.volunteers',
            ],
            'myapes.roles.admin_groups' => ['intranet.administrator'],
            'myapes.roles.superadmin_groups' => ['intranet.superadmin'],
        ]);

        $this->identityProvider = new FakeOidcIdentityProvider;
        $this->directory = new FakeLdapGroupResolver;

        $this->app->instance(OidcIdentityProvider::class, $this->identityProvider);
        $this->app->instance(LdapGroupResolver::class, $this->directory);
    }

    public function test_successful_callback_creates_an_eligible_user_and_starts_the_revalidation_window(): void
    {
        $this->identityProvider->identity = new OidcIdentity(
            'cloudron-subject-1',
            'STAFF@EXAMPLE.COM',
            'APES Staff Member',
        );
        $this->directory->groups = ['POSITION.STAFF', 'position.staff'];

        $response = $this->get(route('staff.auth.callback'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas(RevalidateDirectoryAccess::SESSION_KEY);
        $this->assertAuthenticated();
        $this->assertSame(['staff@example.com'], $this->directory->resolvedEmails);

        $user = User::query()->sole();
        $this->assertSame('cloudron-subject-1', $user->oidc_sub);
        $this->assertSame('staff@example.com', $user->email);
        $this->assertSame('APES Staff Member', $user->name);
        $this->assertSame(User::ROLE_STAFF, $user->role);
        $this->assertSame(['position.staff'], $user->ldap_groups);
        $this->assertTrue($user->email_verified_at->isSameSecond(now()));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.login_success',
            'user_id' => $user->id,
        ]);
    }

    public function test_callback_denies_an_identity_without_an_approved_group_without_creating_a_user(): void
    {
        $this->identityProvider->identity = new OidcIdentity(
            'cloudron-subject-2',
            'visitor@example.com',
            'Directory Visitor',
        );
        $this->directory->groups = ['unrelated.group'];

        $response = $this->get(route('staff.auth.callback'));

        $response->assertForbidden();
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.oidc_access_denied',
        ]);
    }

    public function test_callback_distinguishes_a_missing_directory_identity_from_an_outage(): void
    {
        $this->identityProvider->identity = new OidcIdentity(
            'cloudron-subject-3',
            'missing@example.com',
            'Missing User',
        );
        $this->directory->failure = new DirectoryIdentityNotFound('not found');

        $this->get(route('staff.auth.callback'))->assertForbidden();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.oidc_access_denied',
        ]);

        $this->directory->failure = new DirectoryUnavailable('bind secret must not be exposed');

        $response = $this->get(route('staff.auth.callback'));

        $response->assertStatus(503);
        $response->assertDontSee('bind secret');
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.ldap_resolution_failed',
        ]);
    }

    public function test_missing_directory_identity_revokes_a_known_oidc_account(): void
    {
        $user = User::factory()->create([
            'oidc_sub' => 'revoked-subject',
            'email' => 'revoked@example.com',
            'role' => User::ROLE_ADMIN,
            'ldap_groups' => ['intranet.administrator'],
        ]);
        $this->identityProvider->identity = new OidcIdentity(
            'revoked-subject',
            'revoked@example.com',
            'Revoked User',
        );
        $this->directory->failure = new DirectoryIdentityNotFound('not found');

        $this->get(route('staff.auth.callback'))->assertForbidden();

        $user->refresh();
        $this->assertSame(User::ROLE_SERVICE_USER, $user->role);
        $this->assertSame([], $user->ldap_groups);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.directory_access_revoked',
            'user_id' => $user->id,
        ]);
    }

    public function test_callback_preserves_the_safe_email_collision_boundary(): void
    {
        User::factory()->create([
            'oidc_sub' => null,
            'email' => 'Existing@Example.com',
            'role' => User::ROLE_SERVICE_USER,
        ]);
        $this->identityProvider->identity = new OidcIdentity(
            'different-subject',
            'existing@example.com',
            'Different Identity',
        );
        $this->directory->groups = ['position.staff'];

        $response = $this->get(route('staff.auth.callback'));

        $response->assertForbidden();
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);

        $context = AuditLog::query()
            ->where('event', 'auth.oidc_email_conflict')
            ->sole()
            ->context;
        $this->assertSame(['reason' => 'email_already_in_use'], $context);
    }

    public function test_callback_requires_both_email_and_subject_claims(): void
    {
        $this->identityProvider->identity = new OidcIdentity(
            'subject-without-email',
            null,
            'No Email',
        );

        $this->get(route('staff.auth.callback'))->assertForbidden();
        $this->assertSame([], $this->directory->resolvedEmails);

        $this->identityProvider->identity = new OidcIdentity(
            null,
            'no-subject@example.com',
            'No Subject',
        );

        $this->get(route('staff.auth.callback'))->assertForbidden();
        $this->assertSame([], $this->directory->resolvedEmails);
        $this->assertDatabaseCount('users', 0);
    }
}
