<?php

namespace App\Modules\Detectors;

use App\Contracts\ModuleActiveRecordDetector;
use App\Models\PetProfile;
use App\Modules\ModuleInstanceDefinition;

class PetProfileActiveRecordDetector implements ModuleActiveRecordDetector
{
    public function count(ModuleInstanceDefinition $instance): int
    {
        $domain = match ($instance->subCore->key) {
            'shelter-rescue' => PetProfile::DOMAIN_SHELTER,
            'pet-care-clinic' => PetProfile::DOMAIN_PETCARE,
            default => null,
        };

        return $domain === null
            ? 0
            : PetProfile::query()->where('service_domain', $domain)->count();
    }
}
