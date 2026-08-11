<?php

namespace App\Modules\Activity;

use App\Contracts\ModuleRecentActivityProvider;
use App\Models\SupportTicket;
use App\Models\User;
use App\Modules\ModuleInstanceDefinition;
use App\Modules\ModuleRecentActivityItem;

class SupportTicketRecentActivityProvider implements ModuleRecentActivityProvider
{
    public function recent(
        ModuleInstanceDefinition $instance,
        User $user,
        int $limit = 5,
    ): array {
        return SupportTicket::query()
            ->forSubCore($instance->subCore->key)
            ->visibleTo($user, $instance->subCore->key)
            ->latest('updated_at')
            ->limit(max(0, min($limit, 5)))
            ->get()
            ->map(fn (SupportTicket $ticket): ModuleRecentActivityItem => new ModuleRecentActivityItem(
                $instance->key(),
                'tickets',
                'Ticket',
                $ticket->subject,
                $ticket->status,
                $ticket->priority,
                $ticket->updated_at,
                'apes-cic.tickets.show',
                $ticket->id,
            ))
            ->all();
    }
}
