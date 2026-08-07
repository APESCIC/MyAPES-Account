<?php

namespace App\Contracts;

use App\Modules\ModuleInstanceDefinition;

interface ModuleActiveRecordDetector
{
    public function count(ModuleInstanceDefinition $instance): int;
}
