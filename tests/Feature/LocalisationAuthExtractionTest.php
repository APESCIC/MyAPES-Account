<?php

namespace Tests\Feature;

use App\Core\Accounts\User;
use App\Notifications\PendingFirstLoginChaseNotification;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LocalisationAuthExtractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_implements_has_locale_preference_defaulting_to_en_gb(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(HasLocalePreference::class, $user);
        $this->assertSame('en_GB', $user->preferredLocale());
    }

    public function test_auth_screens_resolve_translated_copy(): void
    {
        $this->get(route('public.login'))
            ->assertOk()
            ->assertSeeText(__('auth.public_login.heading'))
            ->assertSeeText(__('auth.common.forgot_password_link'));

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSeeText(__('auth.forgot_password.heading'))
            ->assertSessionMissing('errors');

        $this->get(route('staff.login'))
            ->assertOk()
            ->assertSeeText(__('auth.staff_login.heading'))
            ->assertSeeText(__('auth.common.continue_cloudron'));
    }

    public function test_password_reset_status_comes_from_lang(): void
    {
        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'nobody@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', __('passwords.request_status'));
    }

    public function test_pending_first_login_mail_uses_lang_keys(): void
    {
        Notification::fake();

        $actor = User::factory()->create(['name' => 'Admin Actor']);
        $pending = User::factory()->create(['name' => 'Pending Staff']);

        $pending->notify(new PendingFirstLoginChaseNotification($actor));

        Notification::assertSentTo(
            $pending,
            PendingFirstLoginChaseNotification::class,
            function (PendingFirstLoginChaseNotification $notification) use ($pending): bool {
                $mail = $notification->toMail($pending);

                $this->assertSame(__('mail.pending_first_login.subject'), $mail->subject);
                $this->assertSame(
                    __('mail.pending_first_login.greeting', ['name' => $pending->name]),
                    $mail->greeting,
                );
                $this->assertContains(__('mail.pending_first_login.line_request'), $mail->introLines);
                $this->assertSame(__('mail.pending_first_login.action'), $mail->actionText);

                return true;
            },
        );
    }

    public function test_flash_and_auth_oidc_keys_are_published(): void
    {
        $this->assertSame('Password updated.', __('flash.password_updated'));
        $this->assertSame(
            'Cloudron sign-in is temporarily unavailable.',
            __('auth.oidc.unavailable'),
        );
        $this->assertSame('full name', __('validation.attributes.name'));
    }
}
