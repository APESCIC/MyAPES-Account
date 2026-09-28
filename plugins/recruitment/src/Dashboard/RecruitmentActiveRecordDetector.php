<?php

namespace Plugins\Recruitment\Dashboard;

use App\Contracts\ModuleActiveRecordDetector;
use Plugins\Recruitment\Models\RecruitmentRole;
use App\Modules\ModuleInstanceDefinition;

class RecruitmentActiveRecordDetector implements ModuleActiveRecordDetector
{
    public function count(ModuleInstanceDefinition $instance): int
    {
        return RecruitmentRole::query()->count();
    }
}
