<?php

namespace Plugins\Consultations\Dashboard;

use App\Contracts\ModuleActiveRecordDetector;
use Plugins\Consultations\Models\PetCareConsultation;
use App\Modules\ModuleInstanceDefinition;

class PetCareConsultationActiveRecordDetector implements ModuleActiveRecordDetector
{
    public function count(ModuleInstanceDefinition $instance): int
    {
        return PetCareConsultation::query()
            ->forPetCareDomain()
            ->whereNull('closed_at')
            ->where('status', '<>', 'closed')
            ->count();
    }
}
