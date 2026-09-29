<?php

namespace App\Notifications\Auth;

use App\Notifications\Auth\Concerns\BuildsAuthMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use BuildsAuthMail;
    use Queueable;

    public function __construct(
        #[\SensitiveParameter]
        public string $token,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expireMinutes = (int) config(
            'auth.passwords.'.config('auth.defaults.passwords').'.expire',
            60,
        );

        return $this->authMail()
            ->subject(__('mail.auth.reset.subject'))
            ->markdown('mail.auth.reset-password', [
                'url' => $this->resetUrl($notifiable),
                'name' => $this->recipientName($notifiable),
                'expireMinutes' => $expireMinutes,
            ]);
    }

    protected function resetUrl(object $notifiable): string
    {
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
