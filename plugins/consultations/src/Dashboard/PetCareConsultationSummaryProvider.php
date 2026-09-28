<?php

namespace Plugins\Consultations\Dashboard;

use App\Contracts\ModuleAggregateSummaryProvider;
use App\Core\Accounts\User;
use Plugins\Consultations\Models\PetCareConsultation;
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
            $instance->subCore->key,
            $instance->subCore->name,
        );
    }
}
