<?php

namespace Tests\Feature\Auth;

use App\Auth\OidcIdentity;
use App\Contracts\OidcIdentityProvider;
use App\Exceptions\DirectoryUnavailable;
use App\Http\Cookies\OidcReauthenticationCookie;
use App\Models\User;
use App\Services\LdapGroupResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\Fakes\FakeLdapGroupResolver;
use Tests\Fakes\FakeOidcIdentityProvider;
use Tests\TestCase;

class ForceReauthenticationCookieTest extends TestCase
{
    use RefreshDatabase;

    private FakeOidcIdentityProvider $identityProvider;

    private FakeLdapGroupResolver $directory;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'session.secure' => true,
            'session.domain' => null,
            'myapes.roles.staff_groups' => ['position.staff'],
            'myapes.roles.admin_groups' => ['intranet.administrator'],
            'myapes.roles.superadmin_groups' => ['intranet.superadmin'],
        ]);

        $this->identityProvider = new FakeOidcIdentityProvider;
        $this->directory = new FakeLdapGroupResolver;

        $this->app->instance(OidcIdentityProvider::class, $this->identityProvider);
        $this->app->instance(LdapGroupResolver::class, $this->directory);
    }

    public function test_explicit_oidc_logout_sets_an_encrypted_year_long_secure_marker(): void
    {
        $now = CarbonImmutable::parse('2026-07-24 16:30:00', 'UTC');
        $this->travelTo($now);
        $user = User::factory()->create([
            'oidc_sub' => 'directory-user',
            'role' => User::ROLE_STAFF,
            'ldap_groups' => ['position.staff'],
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('auth.logout'));

        $response->assertRedirect(route('home'));
        $response->assertCookie(OidcReauthenticationCookie::NAME, '1');
        $this->assertGuest();

        $cookie = $response->getCookie(OidcReauthenticationCookie::NAME, false);
        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertNotSame('1', $cookie->getValue());
        $this->assertSame('/staff/auth', $cookie->getPath());
        $this->assertNull($cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        $this->assertEqualsWithDelta(
            $now->addYear()->timestamp,
            $cookie->getExpiresTime(),
            2,
        );
    }

    public function test_public_logout_does_not_set_the_oidc_marker(): void
    {
        $user = User::factory()->create([
            'oidc_sub' => null,
            'role' => User::ROLE_SERVICE_USER,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('auth.logout'));

        $response->assertRedirect(route('home'));
        $response->assertCookieMissing(OidcReauthenticationCookie::NAME);
        $this->assertGuest();
    }

    public function test_marker_requests_forced_provider_authentication_and_survives_an_abandoned_flow(): void
    {
        $response = $this
            ->withCookie(OidcReauthenticationCookie::NAME, '1')
            ->get(route('staff.auth.login'));

        $response->assertRedirect($this->identityProvider->authorizationUrl);
        $response->assertCookieMissing(OidcReauthenticationCookie::NAME);
        $this->assertTrue($this->identityProvider->forceReauthentication);
        $this->assertSame(1, $this->identityProvider->authorizationCalls);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.oidc_reauthentication_required',
        ]);
    }

    public function test_successful_callback_clears_the_marker_using_the_matching_path(): void
    {
        $this->identityProvider->identity = new OidcIdentity(
            'successful-subject',
            'staff@example.com',
            'Staff Member',
        );
        $this->directory->groups = ['position.staff'];

        $response = $this
            ->withCookie(OidcReauthenticationCookie::NAME, '1')
            ->get(route('staff.auth.callback'));

        $response->assertRedirect(route('dashboard'));
        $response->assertCookieExpired(OidcReauthenticationCookie::NAME);

        $cookie = $response->getCookie(OidcReauthenticationCookie::NAME, false);
        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertSame('/staff/auth', $cookie->getPath());
    }

    public function test_directory_failure_does_not_clear_the_marker(): void
    {
        $this->identityProvider->identity = new OidcIdentity(
            'failed-subject',
            'staff@example.com',
            'Staff Member',
        );
        $this->directory->failure = new DirectoryUnavailable('directory unavailable');

        $response = $this
            ->withCookie(OidcReauthenticationCookie::NAME, '1')
            ->get(route('staff.auth.callback'));

        $response->assertStatus(503);
        $response->assertCookieMissing(OidcReauthenticationCookie::NAME);
        $this->assertGuest();
    }
}
