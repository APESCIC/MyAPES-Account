<?php

namespace App\Notifications\Auth\Concerns;

use Illuminate\Notifications\Messages\MailMessage;

trait BuildsAuthMail
{
    protected function authMail(): MailMessage
    {
        return (new MailMessage)
            ->from(
                (string) config('mail.from.address'),
                __('terms.app_name'),
            );
    }

    protected function recipientName(object $notifiable): string
    {
        $name = $notifiable->name ?? null;

        return is_string($name) && trim($name) !== ''
            ? trim($name)
            : __('mail.auth.greeting_fallback');
    }
}
