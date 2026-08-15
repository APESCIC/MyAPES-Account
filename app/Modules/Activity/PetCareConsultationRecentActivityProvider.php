<?php

namespace App\Modules\Activity;

use App\Contracts\ModuleRecentActivityProvider;
use App\Models\PetCareConsultation;
use App\Models\User;
use App\Modules\ModuleInstanceDefinition;
use App\Modules\ModuleRecentActivityItem;

class PetCareConsultationRecentActivityProvider implements ModuleRecentActivityProvider
{
    public function recent(
        ModuleInstanceDefinition $instance,
        User $user,
        int $limit = 5,
    ): array {
        return PetCareConsultation::query()
            ->forPetCareDomain()
            ->visibleTo($user)
            ->latest('updated_at')
            ->limit(max(0, min($limit, 5)))
            ->get()
            ->map(fn (PetCareConsultation $consultation): ModuleRecentActivityItem => new ModuleRecentActivityItem(
                $instance->key(),
                'consultations',
                'Consultation',
                $consultation->subject,
                $consultation->status,
                null,
                $consultation->updated_at,
                'petcare.consultations.show',
                $consultation->id,
            ))
            ->all();
    }
}
