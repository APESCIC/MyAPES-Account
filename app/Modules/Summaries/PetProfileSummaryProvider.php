<?php

namespace App\Modules\Summaries;

use App\Contracts\ModuleAggregateSummaryProvider;
use App\Models\PetProfile;
use App\Models\User;
use App\Modules\ModuleInstanceDefinition;
use App\Modules\ModuleSummary;

class PetProfileSummaryProvider implements ModuleAggregateSummaryProvider
{
    public function summarize(
        ModuleInstanceDefinition $instance,
        User $user,
    ): ModuleSummary {
        [$domain, $route] = match ($instance->subCore->key) {
            'shelter-rescue' => [
                PetProfile::DOMAIN_SHELTER,
                'shelter.pets.index',
            ],
            'pet-care-clinic' => [
                PetProfile::DOMAIN_PETCARE,
                'petcare.pets.index',
            ],
            default => throw new \LogicException(
                'Pet Profiles summary requested for an incompatible sub-core.',
            ),
        };
        $total = PetProfile::query()
            ->where('service_domain', $domain)
            ->visibleTo($user, $domain)
            ->count();

        return new ModuleSummary(
            $instance->key(),
            'Pet profiles',
            $total,
            null,
            $route,
            'heart',
            'pet',
            'Profiles in this service',
            $instance->subCore->key,
            $instance->subCore->name,
        );
    }
}
