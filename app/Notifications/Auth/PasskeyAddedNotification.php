<?php

namespace App\Notifications\Auth;

use App\Notifications\Auth\Concerns\BuildsAuthMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when a passkey is added (#232).
 */
class PasskeyAddedNotification extends Notification implements ShouldQueue
{
    use BuildsAuthMail;
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->authMail()
            ->subject(__('mail.auth.passkey_added.subject'))
            ->markdown('mail.auth.security-event', [
                'greeting' => __('mail.auth.passkey_added.greeting', [
                    'name' => $this->recipientName($notifiable),
                ]),
                'intro' => __('mail.auth.passkey_added.intro'),
                'url' => route('profile.edit'),
                'action' => __('mail.auth.passkey_added.action'),
                'outro' => __('mail.auth.passkey_added.outro'),
            ]);
    }
}
