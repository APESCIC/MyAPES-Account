<?php

namespace App\Modules\Detectors;

use App\Contracts\ModuleActiveRecordDetector;
use App\Modules\ModuleInstanceDefinition;

/**
 * Stub detector until RecruitmentRole records ship (Wave 1).
 */
class RecruitmentActiveRecordDetector implements ModuleActiveRecordDetector
{
    public function count(ModuleInstanceDefinition $instance): int
    {
        return 0;
    }
}
