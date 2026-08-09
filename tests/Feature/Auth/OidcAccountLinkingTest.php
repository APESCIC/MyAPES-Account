<?php

namespace Tests\Feature\Auth;

use App\Auth\OidcFlow;
use App\Auth\OidcIdentity;
use App\Contracts\OidcIdentityProvider;
use App\Models\User;
use App\Services\LdapGroupResolver;
use App\Services\SessionAuthorizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeLdapGroupResolver;
use Tests\Fakes\FakeOidcIdentityProvider;
use Tests\TestCase;

class OidcAccountLinkingTest extends TestCase
{
    use RefreshDatabase;

    private FakeOidcIdentityProvider $identityProvider;

    private FakeLdapGroupResolver $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->identityProvider = new FakeOidcIdentityProvider;
        $this->directory = new FakeLdapGroupResolver;
        $this->app->instance(OidcIdentityProvider::class, $this->identityProvider);
        $this->app->instance(LdapGroupResolver::class, $this->directory);
    }

    public function test_verified_public_account_links_only_after_password_and_current_oidc_proof(): void
    {
        $user = User::factory()->create([
            'email' => 'public@example.com',
            'password' => 'password',
            'onboarding_completed_at' => now(),
        ]);
        $this->identityProvider->identity = new OidcIdentity(
            'linked-subject',
            'PUBLIC@EXAMPLE.COM',
            'Directory Name',
        );
        $this->directory->groups = ['MYAPES.STAFF'];

        $this->actingAsPassword($user)
            ->post(route('profile.oidc-link.start'), ['current_password' => 'password'])
            ->assertRedirect($this->identityProvider->authorizationUrl);

        $this->assertSame(OidcFlow::AccountLink, $this->identityProvider->authorizationFlow);
        $this->assertTrue($this->identityProvider->forceReauthentication);
        $this->assertDatabaseCount('oidc_link_intents', 1);

        $this->get(route('staff.auth.callback'))->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertSame('linked-subject', $user->oidc_sub);
        $this->assertSame(User::IDENTITY_HYBRID, $user->identity_type);
        $this->assertSame(
            ['service-user', 'staff'],
            $user->roles()->orderBy('id')->pluck('name')->all(),
        );
        $this->assertNotNull($user->oidcLinkIntents()->sole()->consumed_at);
        $this->assertSame(
            SessionAuthorizationContext::METHOD_CLOUDRON_OIDC,
            session('myapes.authentication_method'),
        );
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.oidc_identity_linked',
            'user_id' => $user->id,
        ]);
    }

    public function test_linking_rejects_mismatched_email_without_changing_identity(): void
    {
        $user = User::factory()->create([
            'email' => 'public@example.com',
            'password' => 'password',
            'onboarding_completed_at' => now(),
        ]);
        $this->identityProvider->identity = new OidcIdentity(
            'wrong-subject',
            'other@example.com',
            'Wrong Person',
        );
        $this->directory->groups = ['myapes.staff'];

        $this->actingAsPassword($user)
            ->post(route('profile.oidc-link.start'), ['current_password' => 'password']);

        $this->get(route('staff.auth.callback'))->assertForbidden();

        $user->refresh();
        $this->assertNull($user->oidc_sub);
        $this->assertSame(User::IDENTITY_LOCAL, $user->identity_type);
        $this->assertSame([], $user->roles()->where('name', 'staff')->pluck('name')->all());
    }

    public function test_linking_requires_the_current_password_and_rejects_consumed_intent_replay(): void
    {
        $user = User::factory()->create([
            'email' => 'public@example.com',
            'password' => 'password',
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAsPassword($user)
            ->post(route('profile.oidc-link.start'), ['current_password' => 'wrong'])
            ->assertSessionHasErrors('current_password');
        $this->assertDatabaseCount('oidc_link_intents', 0);

        $this->identityProvider->identity = new OidcIdentity(
            'linked-subject',
            'public@example.com',
            'Directory Name',
        );
        $this->directory->groups = ['myapes.staff'];
        $this->actingAsPassword($user)
            ->post(route('profile.oidc-link.start'), ['current_password' => 'password']);
        $marker = session('myapes.oidc_link_intent');
        $this->get(route('staff.auth.callback'))->assertRedirect(route('profile.edit'));

        $this->withSession(['myapes.oidc_link_intent' => $marker]);
        $this->get(route('staff.auth.callback'))->assertForbidden();
        $this->assertDatabaseCount('users', 1);
    }

    private function actingAsPassword(User $user): static
    {
        $this->actingAs($user);
        $values = app(SessionAuthorizationContext::class)->valuesFor(
            $user,
            SessionAuthorizationContext::METHOD_PASSWORD,
        );

        return $this->withSession($values);
    }
}
