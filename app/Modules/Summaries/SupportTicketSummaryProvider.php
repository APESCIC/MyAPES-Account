<?php

namespace App\Modules\Summaries;

use App\Contracts\ModuleAggregateSummaryProvider;
use App\Models\SupportTicket;
use App\Models\User;
use App\Modules\ModuleInstanceDefinition;
use App\Modules\ModuleSummary;

class SupportTicketSummaryProvider implements ModuleAggregateSummaryProvider
{
    public function summarize(
        ModuleInstanceDefinition $instance,
        User $user,
    ): ModuleSummary {
        $query = SupportTicket::query()->visibleTo($user);
        $open = (clone $query)
            ->whereNull('closed_at')
            ->whereNotIn('status', ['resolved', 'closed'])
            ->count();
        $highPriority = (clone $query)
            ->whereNull('closed_at')
            ->whereNotIn('status', ['resolved', 'closed'])
            ->whereIn('priority', ['high', 'urgent'])
            ->count();

        return new ModuleSummary(
            $instance->key(),
            'Tickets',
            (clone $query)->count(),
            $open,
            'apes-cic.tickets.index',
            'ticket',
            'ticket',
            "{$open} open · {$highPriority} high priority",
        );
    }
}
