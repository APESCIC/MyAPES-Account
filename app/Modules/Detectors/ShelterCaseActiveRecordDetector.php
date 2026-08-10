<?php

namespace App\Modules\Detectors;

use App\Contracts\ModuleActiveRecordDetector;
use App\Models\ShelterCase;
use App\Modules\ModuleInstanceDefinition;

class ShelterCaseActiveRecordDetector implements ModuleActiveRecordDetector
{
    public function count(ModuleInstanceDefinition $instance): int
    {
        $query = ShelterCase::query()
            ->forSubCore($instance->subCore->key)
            ->whereNull('closed_at');

        if ($instance->subCore->key === ShelterCase::SUB_CORE_APES_CIC) {
            $query->whereNotIn('status', ['resolved', 'closed']);
        } else {
            $query->where('status', '<>', 'closed');
        }

        return $query->count();
    }
}
