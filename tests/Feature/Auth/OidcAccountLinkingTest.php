<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\AuthorizationProfile;
use App\Services\SessionAuthorizationContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OidcAccountLinkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cloudron_account_linking_route_is_removed(): void
    {
        $user = User::factory()->create([
            'email' => 'public@example.com',
            'password' => 'password',
            'onboarding_completed_at' => now(),
        ]);

        $this->actingAsPassword($user)
            ->post('/profile/link-cloudron', ['current_password' => 'password'])
            ->assertNotFound();

        $this->actingAsPassword($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('Link Cloudron identity')
            ->assertDontSee('Cloudron staff identity');
    }

    public function test_staff_eligible_accounts_cannot_use_public_password_login(): void
    {
        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create([
                'email' => 'former-hybrid@example.com',
                'password' => 'password',
                'identity_type' => User::IDENTITY_CLOUDRON_OIDC,
                'oidc_sub' => 'former-hybrid-subject',
            ]);

        $this->post(route('public.login.submit'), [
            'email' => $staff->email,
            'password' => 'password',
        ])
            ->assertRedirect(route('staff.login'))
            ->assertSessionHasErrors();

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'auth.public_login_blocked_for_staff',
            'user_id' => $staff->id,
        ]);
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
