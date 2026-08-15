<?php

namespace App\Modules\Summaries;

use App\Contracts\ModuleAggregateSummaryProvider;
use App\Models\PetCareConsultation;
use App\Models\User;
use App\Modules\ModuleInstanceDefinition;
use App\Modules\ModuleSummary;

class PetCareConsultationSummaryProvider implements ModuleAggregateSummaryProvider
{
    public function summarize(
        ModuleInstanceDefinition $instance,
        User $user,
    ): ModuleSummary {
        $query = PetCareConsultation::query()
            ->forPetCareDomain()
            ->visibleTo($user);
        $open = (clone $query)
            ->whereNull('closed_at')
            ->where('status', '<>', 'closed')
            ->count();

        return new ModuleSummary(
            $instance->key(),
            'Consultations',
            (clone $query)->count(),
            $open,
            'petcare.consultations.index',
            'messages-square',
            'consultation',
            "{$open} open",
        );
    }
}
