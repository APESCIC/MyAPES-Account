<?php

namespace App\Modules;

use DateTimeInterface;

final readonly class ModuleRecentActivityItem
{
    public function __construct(
        public string $instanceKey,
        public string $moduleKey,
        public string $label,
        public string $title,
        public string $status,
        public ?string $priority,
        public DateTimeInterface $updatedAt,
        public string $routeName,
        public int $recordId,
    ) {}
}
