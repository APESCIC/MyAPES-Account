<?php

namespace App\Notifications;

use App\Models\ShelterCase;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApesCicCaseUpdatedNotification extends Notification
{
    public function __construct(
        private readonly ShelterCase $case,
        private readonly User $actor,
        private readonly string $eventLabel,
        private readonly string $subCoreKey,
        private readonly string $showRouteName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("APES CIC case #{$this->case->id} {$this->eventLabel}")
            ->line("Case #{$this->case->id} was {$this->eventLabel} by {$this->actor->name}.")
            ->line("Status: {$this->case->status}")
            ->line("Priority: {$this->case->priority}")
            ->action('Open case', route($this->showRouteName, $this->case));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->eventLabel,
            'service' => $this->subCoreKey,
            'module' => 'cases',
            'case_id' => $this->case->id,
            'status' => $this->case->status,
            'priority' => $this->case->priority,
            'updated_by' => $this->actor->name,
            'url' => route($this->showRouteName, $this->case),
        ];
    }
}
