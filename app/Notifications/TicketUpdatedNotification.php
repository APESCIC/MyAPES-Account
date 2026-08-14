<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketUpdatedNotification extends Notification
{
    public function __construct(
        private readonly SupportTicket $ticket,
        private readonly User $actor,
        private readonly string $eventLabel,
        private readonly string $subCoreKey,
        private readonly string $showRouteName,
        private readonly string $serviceName,
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
            ->subject("{$this->serviceName} ticket #{$this->ticket->id} {$this->eventLabel}")
            ->line("Ticket #{$this->ticket->id} ({$this->ticket->subject}) was {$this->eventLabel} by {$this->actor->name}.")
            ->line("Status: {$this->ticket->status}")
            ->line("Priority: {$this->ticket->priority}")
            ->action('Open ticket', route($this->showRouteName, $this->ticket));
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
            'service' => $this->subCoreKey,
            'module' => 'tickets',
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
            'status' => $this->ticket->status,
            'priority' => $this->ticket->priority,
            'updated_by' => $this->actor->name,
            'url' => route($this->showRouteName, $this->ticket),
        ];
    }
}
