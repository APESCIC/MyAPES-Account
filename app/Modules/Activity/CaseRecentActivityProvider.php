<?php

namespace App\Modules\Activity;

use App\Contracts\ModuleRecentActivityProvider;
use App\Models\PetProfile;
use App\Models\ShelterCase;
use App\Models\User;
use App\Modules\ModuleInstanceDefinition;
use App\Modules\ModuleRecentActivityItem;

class CaseRecentActivityProvider implements ModuleRecentActivityProvider
{
    public function recent(
        ModuleInstanceDefinition $instance,
        User $user,
        int $limit = 5,
    ): array {
        $route = $instance->subCore->key === ShelterCase::SUB_CORE_APES_CIC
            ? 'apes-cic.cases.show'
            : 'shelter.cases.show';
        $query = ShelterCase::query()
            ->forSubCore($instance->subCore->key)
            ->visibleTo($user, $instance->subCore->key);
        if ($instance->subCore->key === ShelterCase::SUB_CORE_SHELTER_RESCUE) {
            $query->whereHas(
                'petProfile',
                static fn ($pets) => $pets->where(
                    'service_domain',
                    PetProfile::DOMAIN_SHELTER,
                ),
            );
        }

        return $query
            ->latest('updated_at')
            ->limit(max(0, min($limit, 5)))
            ->get()
            ->map(fn (ShelterCase $case): ModuleRecentActivityItem => new ModuleRecentActivityItem(
                $instance->key(),
                'cases',
                'Case',
                $case->title,
                $case->status,
                $case->priority,
                $case->updated_at,
                $route,
                $case->id,
            ))
            ->all();
    }
}
