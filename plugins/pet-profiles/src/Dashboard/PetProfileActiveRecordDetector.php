<?php

namespace Plugins\PetProfiles\Dashboard;

use App\Contracts\ModuleActiveRecordDetector;
use App\Modules\ModuleInstanceDefinition;
use Plugins\PetProfiles\Models\PetProfile;

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
