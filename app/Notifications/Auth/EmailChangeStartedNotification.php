<?php

namespace App\Notifications\Auth;

use App\Notifications\Auth\Concerns\BuildsAuthMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Stub for Wave 4 (#228) — sent when a secure email change is started.
 */
class EmailChangeStartedNotification extends Notification implements ShouldQueue
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
            ->subject(__('mail.auth.email_change_started.subject'))
            ->markdown('mail.auth.security-event', [
                'greeting' => __('mail.auth.email_change_started.greeting', [
                    'name' => $this->recipientName($notifiable),
                ]),
                'intro' => __('mail.auth.email_change_started.intro'),
                'url' => route('profile.edit'),
                'action' => __('mail.auth.email_change_started.action'),
                'outro' => __('mail.auth.email_change_started.outro'),
            ]);
    }
}
