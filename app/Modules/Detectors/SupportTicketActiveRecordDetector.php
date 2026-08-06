<?php

namespace App\Modules\Detectors;

use App\Contracts\ModuleActiveRecordDetector;
use App\Models\SupportTicket;
use App\Modules\ModuleInstanceDefinition;

class SupportTicketActiveRecordDetector implements ModuleActiveRecordDetector
{
    public function count(ModuleInstanceDefinition $instance): int
    {
        return SupportTicket::query()
            ->whereNull('closed_at')
            ->whereNotIn('status', ['closed', 'resolved'])
            ->count();
    }
}
