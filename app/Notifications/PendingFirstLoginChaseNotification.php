<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingFirstLoginChaseNotification extends Notification
{
    public function __construct(
        private readonly User $actor,
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
        $staffLoginUrl = route('staff.login');

        return (new MailMessage)
            ->subject('Complete your MyAPES Staff Login')
            ->greeting('Hello '.$notifiable->name.',')
            ->line(
                'An APES administrator asked you to complete your first Staff Login so your Cloudron directory account can link to MyAPES Account.',
            )
            ->line(
                'Use Staff Login and continue with APES Cloudron. Your password and passkeys stay on Cloudron — this message is not a public password reset.',
            )
            ->action('Open Staff Login', $staffLoginUrl)
            ->line(
                'If the button does not work, open this address: '.$staffLoginUrl,
            )
            ->salutation('— MyAPES Account');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'pending_first_login_chase',
            'chased_by' => $this->actor->id,
            'url' => route('staff.login'),
        ];
    }
}
