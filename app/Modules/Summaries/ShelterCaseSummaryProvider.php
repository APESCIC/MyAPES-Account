<?php

namespace App\Modules\Summaries;

use App\Contracts\ModuleAggregateSummaryProvider;
use App\Models\ShelterCase;
use App\Models\User;
use App\Modules\ModuleInstanceDefinition;
use App\Modules\ModuleSummary;

class ShelterCaseSummaryProvider implements ModuleAggregateSummaryProvider
{
    public function summarize(
        ModuleInstanceDefinition $instance,
        User $user,
    ): ModuleSummary {
        $query = ShelterCase::query()->visibleTo($user);
        $open = (clone $query)
            ->whereNull('closed_at')
            ->where('status', '<>', 'closed')
            ->count();

        return new ModuleSummary(
            $instance->key(),
            'Shelter cases',
            (clone $query)->count(),
            $open,
            'shelter.cases.index',
            'house',
            'shelter',
            "{$open} open",
        );
    }
}
