<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuthorizationProfile;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PublicLocalForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const GENERIC_STATUS = 'If a local public account exists for that email, we have sent a password reset link.';

    private const NEW_PASSWORD = 'Correct-horse-42-reset!';

    public function test_login_and_register_expose_forgot_password_and_autocomplete_hints(): void
    {
        $login = $this->get(route('public.login'));
        $loginHtml = $login->getContent();
        $login->assertOk()
            ->assertSee('href="'.route('password.request').'"', false)
            ->assertSeeText('Forgot password')
            ->assertSee('autocomplete="username"', false)
            ->assertSee('autocomplete="current-password"', false);
        $this->assertDoesNotMatchRegularExpression(
            '/<(input|select|textarea)[^>]*(name="login"|id="login"|name="password"|id="password")[^>]*autocomplete="off"/i',
            $loginHtml,
        );
        $this->assertDoesNotOfferImpersonation($loginHtml);

        $register = $this->get(route('public.register'));
        $register->assertOk()
            ->assertSee('autocomplete="username"', false)
            ->assertSee('autocomplete="new-password"', false);
        $this->assertDoesNotOfferImpersonation($register->getContent());

        $this->get(route('staff.login'))
            ->assertOk()
            ->assertDontSee(route('password.request'), false)
            ->assertDontSeeText('Forgot password');
    }

    public function test_guest_can_request_and_complete_reset_for_a_local_public_account(): void
    {
        Notification::fake();

        $public = User::factory()->create([
            'email' => 'local.public.reset@example.com',
            'password' => 'password',
        ]);
        $originalToken = $public->remember_token;
        $originalEpoch = $public->authorization_epoch;

        $forgot = $this->get(route('password.request'));
        $forgot->assertOk()
            ->assertSeeText('Forgot password')
            ->assertSee('autocomplete="username"', false)
            ->assertSee('name="email"', false);
        $this->assertDoesNotOfferImpersonation($forgot->getContent());

        $this->from(route('password.request'))
            ->post(route('password.email'), [
                'email' => 'LOCAL.PUBLIC.RESET@EXAMPLE.COM',
            ])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', self::GENERIC_STATUS);

        Notification::assertSentTo($public, ResetPassword::class);
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $public->email,
        ]);

        $token = '';
        Notification::assertSentTo(
            $public,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return $token !== '';
            },
        );

        $resetPage = $this->get(route('password.reset', [
            'token' => $token,
            'email' => $public->email,
        ]));
        $resetPage->assertOk()
            ->assertSee('autocomplete="username"', false)
            ->assertSee('autocomplete="new-password"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false);
        $this->assertDoesNotMatchRegularExpression(
            '/<(input|select|textarea)[^>]*(name="password"|id="password")[^>]*autocomplete="off"/i',
            $resetPage->getContent(),
        );

        $this->from(route('password.reset', ['token' => $token]))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $public->email,
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($public);

        $public->refresh();
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $public->password));
        $this->assertFalse(Hash::check('password', $public->password));
        $this->assertSame($originalEpoch + 1, $public->authorization_epoch);
        $this->assertNotSame($originalToken, $public->remember_token);
        $this->assertSame('password', session('myapes.authentication_method'));

        $audit = AuditLog::query()
            ->where('event', 'auth.public_local_password_reset')
            ->where('auditable_id', $public->id)
            ->sole();
        $this->assertStringNotContainsString(
            self::NEW_PASSWORD,
            json_encode($audit->toArray(), JSON_THROW_ON_ERROR),
        );
        $this->assertStringNotContainsString(
            $token,
            json_encode($audit->toArray(), JSON_THROW_ON_ERROR),
        );

        $this->post(route('auth.logout'));
        $this->assertGuest();

        $this->post(route('public.login.submit'), [
            'login' => $public->email,
            'password' => 'password',
        ])->assertSessionHasErrors();
        $this->assertGuest();

        $this->post(route('public.login.submit'), [
            'login' => $public->email,
            'password' => self::NEW_PASSWORD,
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($public);
    }

    public function test_directory_cloudron_and_staff_accounts_cannot_use_guest_reset(): void
    {
        Notification::fake();

        $directoryPublic = User::factory()
            ->directoryIdentity('directory-public-subject')
            ->create([
                'email' => 'directory.public@example.com',
                'password' => 'password',
            ]);
        $pendingFirstLogin = User::factory()->create([
            'email' => 'pending.directory@example.com',
            'identity_type' => User::IDENTITY_CLOUDRON_OIDC,
            'oidc_sub' => null,
            'password' => 'password',
        ]);
        $directoryStaff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->directoryIdentity('directory-staff-subject')
            ->create([
                'email' => 'directory.staff@example.com',
                'password' => 'password',
            ]);
        $hybrid = User::factory()->create([
            'email' => 'hybrid.identity@example.com',
            'identity_type' => User::IDENTITY_HYBRID,
            'oidc_sub' => 'hybrid-subject',
            'password' => 'password',
        ]);
        $localStaff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create([
                'email' => 'local.staff@example.com',
                'password' => 'password',
            ]);

        foreach ([
            $directoryPublic,
            $pendingFirstLogin,
            $directoryStaff,
            $hybrid,
            $localStaff,
        ] as $refused) {
            $this->from(route('password.request'))
                ->post(route('password.email'), [
                    'email' => $refused->email,
                ])
                ->assertRedirect(route('password.request'))
                ->assertSessionHas('status', self::GENERIC_STATUS);

            $refused->refresh();
            $this->assertTrue(Hash::check('password', $refused->password));
            $this->assertDatabaseMissing('password_reset_tokens', [
                'email' => $refused->email,
            ]);
        }

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('audit_logs', [
            'event' => 'auth.public_local_password_reset',
        ]);
    }

    public function test_unknown_email_returns_the_same_status_without_sending_mail(): void
    {
        Notification::fake();

        $this->from(route('password.request'))
            ->post(route('password.email'), [
                'email' => 'nobody@example.com',
            ])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', self::GENERIC_STATUS);

        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_reset_tokens_expire_and_do_not_sign_anyone_in(): void
    {
        Notification::fake();

        $public = User::factory()->create([
            'email' => 'expiring.reset@example.com',
            'password' => 'password',
        ]);

        $this->post(route('password.email'), [
            'email' => $public->email,
        ]);

        $token = '';
        Notification::assertSentTo(
            $public,
            ResetPassword::class,
            function (ResetPassword $notification) use (&$token): bool {
                $token = $notification->token;

                return $token !== '';
            },
        );

        $this->travel(61)->minutes();

        $this->from(route('password.reset', ['token' => $token]))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $public->email,
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $public->refresh();
        $this->assertTrue(Hash::check('password', $public->password));
    }

    public function test_a_directory_account_cannot_complete_reset_even_with_a_planted_token(): void
    {
        $directory = User::factory()
            ->directoryIdentity('planted-directory-subject')
            ->create([
                'email' => 'planted.directory@example.com',
                'password' => 'password',
            ]);

        $token = Password::broker()->createToken($directory);

        $this->from(route('password.reset', ['token' => $token]))
            ->post(route('password.update'), [
                'token' => $token,
                'email' => $directory->email,
                'password' => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $directory->refresh();
        $this->assertTrue(Hash::check('password', $directory->password));
        $this->assertDatabaseMissing('audit_logs', [
            'event' => 'auth.public_local_password_reset',
        ]);
    }

    private function assertDoesNotOfferImpersonation(string $html): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/impersonat|log in as|sign in as/i',
            $html,
        );
    }
}
