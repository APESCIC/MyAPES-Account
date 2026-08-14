<?php

namespace App\Modules\Attention;

use App\Contracts\ModuleAttentionProvider;
use App\Models\SupportTicket;
use App\Models\User;
use App\Modules\ModuleAttentionItem;
use App\Modules\ModuleInstanceDefinition;
use App\Services\TicketServiceConfiguration;

class SupportTicketAttentionProvider implements ModuleAttentionProvider
{
    public function __construct(
        private readonly TicketServiceConfiguration $ticketServices,
    ) {}

    public function attention(
        ModuleInstanceDefinition $instance,
        User $user,
        int $limit = 6,
    ): array {
        $ticketService = $this->ticketServices->for($instance->subCore->key);

        return SupportTicket::query()
            ->forSubCore($instance->subCore->key)
            ->visibleTo($user, $instance->subCore->key)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->with('user')
            ->latest('updated_at')
            ->limit(max(0, $limit))
            ->get()
            ->map(fn (SupportTicket $ticket): ModuleAttentionItem => new ModuleAttentionItem(
                $instance->key(),
                'ticket',
                'ticket',
                $ticketService->serviceName,
                'Ticket',
                $ticket->subject,
                $ticket->status,
                $ticket->priority,
                null,
                $ticket->user ? "From: {$ticket->user->name}" : null,
                $ticket->updated_at,
                $ticketService->routePrefix.'.show',
                $ticket->id,
            ))
            ->all();
    }
}
