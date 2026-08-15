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
        [$domain, $route] = match ($instance->subCore->key) {
            'shelter-rescue' => [
                PetProfile::DOMAIN_SHELTER,
                'shelter.pets.show',
            ],
            'pet-care-clinic' => [
                PetProfile::DOMAIN_PETCARE,
                'petcare.pets.show',
            ],
            default => throw new \LogicException(
                'Pet Profiles activity requested for an incompatible sub-core.',
            ),
        };

        return PetProfile::query()
            ->where('service_domain', $domain)
            ->visibleTo($user, $domain)
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
                $route,
                $pet->id,
            ))
            ->all();
    }
}
