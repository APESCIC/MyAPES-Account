<?php

namespace App\Modules\Activity;

use App\Contracts\ModuleRecentActivityProvider;
use App\Models\PetProfile;
use App\Models\User;
use App\Modules\ModuleInstanceDefinition;
use App\Modules\ModuleRecentActivityItem;

class PetProfileRecentActivityProvider implements ModuleRecentActivityProvider
{
    public function recent(
        ModuleInstanceDefinition $instance,
        User $user,
        int $limit = 5,
    ): array {
        return PetProfile::query()
            ->where('service_domain', PetProfile::DOMAIN_SHELTER)
            ->visibleTo($user, PetProfile::DOMAIN_SHELTER)
            ->latest('updated_at')
            ->limit(max(0, min($limit, 5)))
            ->get()
            ->map(fn (PetProfile $pet): ModuleRecentActivityItem => new ModuleRecentActivityItem(
                $instance->key(),
                'pet-profiles',
                'Pet profile',
                $pet->name,
                'active',
                null,
                $pet->updated_at,
                'shelter.pets.show',
                $pet->id,
            ))
            ->all();
    }
}
