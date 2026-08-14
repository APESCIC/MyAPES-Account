<?php

namespace App\Modules;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;

final readonly class ModuleAttentionItem
{
    public CarbonInterface $updatedAt;

    public function __construct(
        public string $instanceKey,
        public string $type,
        public string $icon,
        public string $service,
        public string $label,
        public string $title,
        public string $status,
        public ?string $priority,
        public ?string $context,
        public ?string $owner,
        DateTimeInterface $updatedAt,
        public string $routeName,
        public int $recordId,
    ) {
        $this->updatedAt = CarbonImmutable::instance($updatedAt);
    }
}
