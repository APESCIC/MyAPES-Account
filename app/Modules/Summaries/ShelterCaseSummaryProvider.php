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
        $query = ShelterCase::query()
            ->forSubCore($instance->subCore->key)
            ->visibleTo($user, $instance->subCore->key);
        $open = (clone $query)
            ->whereNull('closed_at')
            ->where('status', '<>', 'closed')
            ->count();

        $isApesCic = $instance->subCore->key === ShelterCase::SUB_CORE_APES_CIC;

        return new ModuleSummary(
            $instance->key(),
            $isApesCic ? 'APES CIC cases' : 'Shelter cases',
            (clone $query)->count(),
            $open,
            $isApesCic ? 'apes-cic.cases.index' : 'shelter.cases.index',
            $isApesCic ? 'briefcase-business' : 'house',
            $isApesCic ? 'ticket' : 'shelter',
            "{$open} open",
        );
    }
}
