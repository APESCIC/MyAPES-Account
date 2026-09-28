<?php

namespace Plugins\Tickets\Dashboard;

use App\Contracts\ModuleAggregateSummaryProvider;
use App\Core\Accounts\User;
use Plugins\Tickets\Models\SupportTicket;
use App\Modules\ModuleInstanceDefinition;
use App\Modules\ModuleSummary;
use Plugins\Tickets\TicketServiceConfiguration;

class SupportTicketSummaryProvider implements ModuleAggregateSummaryProvider
{
    public function __construct(
        private readonly TicketServiceConfiguration $ticketServices,
    ) {}

    public function summarize(
        ModuleInstanceDefinition $instance,
        User $user,
    ): ModuleSummary {
        $query = SupportTicket::query()
            ->forSubCore($instance->subCore->key)
            ->visibleTo($user, $instance->subCore->key);
        $open = (clone $query)
            ->whereNull('closed_at')
            ->whereNotIn('status', ['resolved', 'closed'])
            ->count();
        $highPriority = (clone $query)
            ->whereNull('closed_at')
            ->whereNotIn('status', ['resolved', 'closed'])
            ->whereIn('priority', ['high', 'urgent'])
            ->count();

        $ticketService = $this->ticketServices->for($instance->subCore->key);

        return new ModuleSummary(
            $instance->key(),
            'Tickets',
            (clone $query)->count(),
            $open,
            $ticketService->routePrefix.'.index',
            'ticket',
            'ticket',
            "{$open} open · {$highPriority} high priority",
            $instance->subCore->key,
            $instance->subCore->name,
        );
    }
}
