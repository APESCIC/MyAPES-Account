<?php

namespace App\Contracts;

use App\Modules\ModuleAnalyticsSnapshot;
use App\Modules\ModuleInstanceDefinition;
use DateTimeInterface;

interface ModuleAnalyticsProvider
{
    public function snapshot(
        ModuleInstanceDefinition $instance,
        DateTimeInterface $from,
        DateTimeInterface $to,
        string $timezone,
    ): ModuleAnalyticsSnapshot;
}
