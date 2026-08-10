<?php

namespace App\Modules\Detectors;

use App\Contracts\ModuleActiveRecordDetector;
use App\Models\ShelterCase;
use App\Modules\ModuleInstanceDefinition;

class ShelterCaseActiveRecordDetector implements ModuleActiveRecordDetector
{
    public function count(ModuleInstanceDefinition $instance): int
    {
        return ShelterCase::query()
            ->forSubCore($instance->subCore->key)
            ->whereNull('closed_at')
            ->where('status', '<>', 'closed')
            ->count();
    }
}
