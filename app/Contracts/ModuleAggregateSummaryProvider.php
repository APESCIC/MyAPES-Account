<?php

namespace App\Contracts;

use App\Models\User;
use App\Modules\ModuleInstanceDefinition;
use App\Modules\ModuleSummary;

interface ModuleAggregateSummaryProvider
{
    public function summarize(
        ModuleInstanceDefinition $instance,
        User $user,
    ): ModuleSummary;
}
