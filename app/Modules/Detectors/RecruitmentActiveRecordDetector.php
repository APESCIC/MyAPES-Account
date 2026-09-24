<?php

namespace App\Modules\Detectors;

use App\Contracts\ModuleActiveRecordDetector;
use App\Models\RecruitmentRole;
use App\Modules\ModuleInstanceDefinition;

class RecruitmentActiveRecordDetector implements ModuleActiveRecordDetector
{
    public function count(ModuleInstanceDefinition $instance): int
    {
        return RecruitmentRole::query()->count();
    }
}
