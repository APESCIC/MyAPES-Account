<?php

namespace App\Modules;

final readonly class ModuleAnalyticsSnapshot
{
    /**
     * @param  array<string, int>  $createdPerDay
     * @param  array<string, int>  $closedPerDay
     * @param  array<int, float>  $closureMinutes
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
        public int $currentlyOpen = 0,
        public int $currentlyHighOrUrgent = 0,
        public int $currentlyUnassigned = 0,
        public array $closureMinutes = [],
    ) {}

    public static function empty(string $instanceKey): self
    {
        return new self(
            $instanceKey,
            0,
            0,
            0,
            0,
            [],
            [],
            null,
            0,
            0,
            0,
            0,
            [],
        );
    }
}
