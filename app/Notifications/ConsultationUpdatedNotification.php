<?php

namespace App\Notifications;

use App\Models\PetCareConsultation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConsultationUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly PetCareConsultation $consultation,
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
            ->subject("APES Pet Care consultation #{$this->consultation->id} {$this->eventLabel}")
            ->line("Consultation #{$this->consultation->id} ({$this->consultation->subject}) was {$this->eventLabel} by {$this->actor->name}.")
            ->line("Status: {$this->consultation->status}")
            ->action('Open consultation', route('petcare.consultations.show', $this->consultation));
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
            'service' => 'petcare',
            'consultation_id' => $this->consultation->id,
            'subject' => $this->consultation->subject,
            'status' => $this->consultation->status,
            'updated_by' => $this->actor->name,
            'url' => route('petcare.consultations.show', $this->consultation),
        ];
    }
}
