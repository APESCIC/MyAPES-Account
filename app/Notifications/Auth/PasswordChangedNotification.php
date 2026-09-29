<?php

namespace App\Notifications\Auth;

use App\Notifications\Auth\Concerns\BuildsAuthMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification implements ShouldQueue
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
            ->subject(__('mail.auth.password_changed.subject'))
            ->markdown('mail.auth.password-changed', [
                'url' => route('public.login'),
                'name' => $this->recipientName($notifiable),
            ]);
    }
}
