<?php

namespace App\Modules;

final readonly class ModuleAnalyticsSnapshot
{
    /**
     * @param  array<string, int>  $createdPerDay
     * @param  array<string, int>  $closedPerDay
     */
    public function __construct(
        public string $instanceKey,
        public int $total,
        public int $open,
        public int $highOrUrgent,
        public int $unassigned,
        public array $createdPerDay,
        public array $closedPerDay,
        public ?float $medianClosureMinutes,
        public int $closureSampleSize,
    ) {}
}
