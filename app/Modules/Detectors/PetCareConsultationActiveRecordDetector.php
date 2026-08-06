<?php

namespace App\Modules\Detectors;

use App\Contracts\ModuleActiveRecordDetector;
use App\Models\PetCareConsultation;
use App\Modules\ModuleInstanceDefinition;

class PetCareConsultationActiveRecordDetector implements ModuleActiveRecordDetector
{
    public function count(ModuleInstanceDefinition $instance): int
    {
        return PetCareConsultation::query()
            ->whereNull('closed_at')
            ->where('status', '<>', 'closed')
            ->count();
    }
}
