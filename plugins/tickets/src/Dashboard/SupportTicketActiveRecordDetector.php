<?php

namespace Plugins\Tickets\Dashboard;

use App\Contracts\ModuleActiveRecordDetector;
use Plugins\Tickets\Models\SupportTicket;
use App\Modules\ModuleInstanceDefinition;

class SupportTicketActiveRecordDetector implements ModuleActiveRecordDetector
{
    public function count(ModuleInstanceDefinition $instance): int
    {
        return SupportTicket::query()
            ->forSubCore($instance->subCore->key)
            ->whereNull('closed_at')
            ->whereNotIn('status', ['closed', 'resolved'])
            ->count();
    }
}
