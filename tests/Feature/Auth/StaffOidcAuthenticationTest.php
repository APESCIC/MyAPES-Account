<?php

namespace Tests\Feature\Auth;

use App\Auth\OidcIdentity;
use App\Contracts\OidcIdentityProvider;
use App\Exceptions\DirectoryIdentityNotFound;
use App\Exceptions\DirectoryUnavailable;
use App\Http\Middleware\RevalidateDirectoryAccess;
use App\Models\AuditLog;
use App\Models\RoleSource;
use App\Models\User;
use App\Services\LdapGroupResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $this->directory->groups = ['MYAPES.STAFF', 'myapes.staff'];

        $response = $this->get(route('staff.auth.callback'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas(RevalidateDirectoryAccess::SESSION_KEY);
        $this->assertAuthenticated();
        $this->assertSame(['staff@example.com'], $this->directory->resolvedEmails);

        $user = User::query()->sole();
        $this->assertSame('cloudron-subject-1', $user->oidc_sub);
        $this->assertSame('staff@example.com', $user->email);
        $this->assertSame('APES Staff Member', $user->name);
        $this->assertSame(User::IDENTITY_CLOUDRON_OIDC, $user->identity_type);
        $this->assertSame(User::ROLE_STAFF, $user->accessLevel());
        $this->assertSame(['myapes.staff'], $user->ldap_groups);
        $this->assertTrue($user->email_verified_at->isSameSecond(now()));
        $this->assertNotNull($user->staffProfile);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.login_success',
            'user_id' => $user->id,
        ]);
    }

    public function test_plural_superadmins_group_grants_super_admin_on_staff_login(): void
    {
        $this->identityProvider->identity = new OidcIdentity(
            'plural-superadmins-subject',
            'plural.superadmins@example.com',
            'Plural Superadmins Member',
        );
        $this->directory->groups = ['myapes.superadmins'];

        $response = $this->get(route('staff.auth.callback'));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'plural.superadmins@example.com')->sole();
        $this->assertSame(User::ROLE_SUPERADMIN, $user->accessLevel());
        $this->assertSame(['myapes.superadmins'], $user->ldap_groups);
        $this->assertTrue(
            $user->roles()->where('name', 'super-admin')->exists(),
        );
    }

    public function test_super_admin_callback_redirects_to_recovery_while_maintenance_is_active(): void
    {
        $this->fakeMaintenanceMode(true);
        $this->identityProvider->identity = new OidcIdentity(
            'maintenance-superadmin-subject',
            'maintenance-superadmin@example.com',
            'Maintenance Super Admin',
        );
        $this->directory->groups = ['myapes.superadmin'];

        $this->get(route('staff.auth.callback'))
            ->assertRedirect(route('admin.maintenance.index'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.login_success',
        ]);
    }

    public function test_administrator_callback_returns_maintenance_response_while_maintenance_is_active(): void
    {
        $this->fakeMaintenanceMode(true);
        $this->identityProvider->identity = new OidcIdentity(
            'maintenance-admin-subject',
            'maintenance-admin@example.com',
            'Maintenance Administrator',
        );
        $this->directory->groups = ['myapes.admin'];

        $this->get(route('staff.auth.callback'))
            ->assertServiceUnavailable()
            ->assertSeeText('Temporarily unavailable');

        $this->assertAuthenticated();
    }

    public function test_ordinary_staff_callback_authenticates_but_returns_maintenance_response(): void
    {
        $this->fakeMaintenanceMode(true);
        $this->identityProvider->identity = new OidcIdentity(
            'maintenance-staff-subject',
            'maintenance-staff@example.com',
            'Maintenance Staff',
        );
        $this->directory->groups = ['myapes.staff'];

        $this->get(route('staff.auth.callback'))
            ->assertServiceUnavailable()
            ->assertSeeText('MyAPES Core')
            ->assertSeeText('Temporarily unavailable');

        $this->assertAuthenticated();
        $this->assertSame(
            User::ROLE_STAFF,
            User::query()->where('email', 'maintenance-staff@example.com')->sole()->accessLevel(),
        );
    }

    public function test_callback_uses_the_exact_persisted_mapping_and_disables_remember_me(): void
    {
        $this->identityProvider->identity = new OidcIdentity(
            'exact-mapping-subject',
            'exact-mapping@example.com',
            'Exact Mapping Staff',
        );
        $this->directory->groups = ['MYAPES.STAFF'];

        $response = $this->get(route('staff.auth.callback'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas(
            'myapes.authentication_method',
            'cloudron_oidc',
        );
        $response->assertSessionHas('myapes.authorization_epoch', 1);
        $response->assertSessionHas('myapes.directory_validated_at');

        $user = User::query()->sole();
        $this->assertSame(['staff'], $user->roles()->pluck('name')->all());
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'remember_token' => null,
        ]);
        $this->assertDatabaseHas('role_sources', [
            'user_id' => $user->id,
            'source' => RoleSource::SOURCE_DIRECTORY,
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

    public function test_callback_refuses_login_when_eligibility_changes_before_authoritative_sync(): void
    {
        $this->identityProvider->identity = new OidcIdentity(
            'mapping-race-subject',
            'mapping-race@example.com',
            'Mapping Race Staff',
        );
        $this->directory->groups = ['myapes.staff'];
        User::creating(static function (): void {
            DB::table('directory_group_role_mappings')->delete();
        });

        $response = $this->get(route('staff.auth.callback'));

        $response->assertForbidden();
        $this->assertGuest();
        $this->assertDatabaseMissing('audit_logs', [
            'event' => 'auth.login_success',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.oidc_access_denied',
        ]);
    }

    public function test_callback_denies_a_suspended_known_identity_before_directory_access(): void
    {
        $user = User::factory()
            ->accessLevel(User::ROLE_STAFF)
            ->cloudronIdentity('suspended-directory-subject')
            ->create([
                'email' => 'suspended-directory@example.com',
                'suspended_at' => now(),
                'suspension_reason' => 'Account review',
            ]);
        $this->identityProvider->identity = new OidcIdentity(
            'suspended-directory-subject',
            $user->email,
            $user->name,
        );
        $this->directory->groups = ['myapes.staff'];

        $this->get(route('staff.auth.callback'))->assertForbidden();

        $this->assertGuest();
        $this->assertSame([], $this->directory->resolvedEmails);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.suspended_login_denied',
            'user_id' => $user->id,
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
        $user = User::factory()
            ->accessLevel(User::ROLE_ADMIN)
            ->cloudronIdentity('revoked-subject')
            ->create([
                'email' => 'revoked@example.com',
                'ldap_groups' => ['myapes.admin'],
            ]);
        $this->identityProvider->identity = new OidcIdentity(
            'revoked-subject',
            'revoked@example.com',
            'Revoked User',
        );
        $this->directory->failure = new DirectoryIdentityNotFound('not found');

        $this->get(route('staff.auth.callback'))->assertForbidden();

        $user->refresh();
        $this->assertSame(User::ROLE_SERVICE_USER, $user->accessLevel());
        $this->assertSame([], $user->ldap_groups);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.directory_access_revoked',
            'user_id' => $user->id,
        ]);
    }

    public function test_callback_preserves_the_safe_email_collision_boundary(): void
    {
        User::factory()
            ->accessLevel(User::ROLE_SERVICE_USER)
            ->create([
                'oidc_sub' => null,
                'identity_type' => User::IDENTITY_LOCAL,
                'email' => 'Existing@Example.com',
            ]);
        $this->identityProvider->identity = new OidcIdentity(
            'different-subject',
            'existing@example.com',
            'Different Identity',
        );
        $this->directory->groups = ['myapes.staff'];

        $response = $this->get(route('staff.auth.callback'));

        $response->assertForbidden();
        $response->assertSeeText('Use a different Cloudron or work email');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);

        $context = AuditLog::query()
            ->where('event', 'auth.oidc_email_conflict')
            ->sole()
            ->context;
        $this->assertSame(['reason' => 'email_already_in_use'], $context);
    }

    public function test_callback_converts_a_subject_linked_local_password_account_to_staff_only(): void
    {
        $user = User::factory()
            ->accessLevel(User::ROLE_SERVICE_USER)
            ->create([
                'oidc_sub' => 'linked-local-subject',
                'identity_type' => User::IDENTITY_LOCAL,
                'email' => 'linked-local@example.com',
                'password' => 'linked-local-password',
            ]);
        $passwordHash = $user->password;
        $this->identityProvider->identity = new OidcIdentity(
            'linked-local-subject',
            'linked-local@example.com',
            'Linked Local Account',
        );
        $this->directory->groups = ['myapes.staff'];

        $response = $this->get(route('staff.auth.callback'));

        $response->assertRedirect(route('dashboard'));
        $user->refresh();
        $this->assertSame(User::IDENTITY_CLOUDRON_OIDC, $user->identity_type);
        $this->assertSame($passwordHash, $user->password);
        $this->assertSame(User::ROLE_STAFF, $user->accessLevel());
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->staffProfile);
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
