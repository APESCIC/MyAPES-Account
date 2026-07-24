<?php

namespace App\Notifications;

use App\Models\ShelterCase;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShelterCaseUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly ShelterCase $case,
        private readonly User $actor,
        private readonly string $eventLabel,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("APES Shelter case #{$this->case->id} {$this->eventLabel}")
            ->line("Case #{$this->case->id} ({$this->case->title}) was {$this->eventLabel} by {$this->actor->name}.")
            ->line("Case type: {$this->case->case_type}")
            ->line("Status: {$this->case->status}")
            ->action('Open case', route('shelter.cases.show', $this->case));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->eventLabel,
            'service' => 'shelter',
            'case_id' => $this->case->id,
            'title' => $this->case->title,
            'case_type' => $this->case->case_type,
            'status' => $this->case->status,
            'updated_by' => $this->actor->name,
            'url' => route('shelter.cases.show', $this->case),
        ];
    }
}
