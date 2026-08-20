<?php

namespace App\Modules;

final readonly class ModuleSummary
{
    public function __construct(
        public string $instanceKey,
        public string $label,
        public int $total,
        public ?int $active,
        public string $routeName,
        public string $icon,
        public string $style,
        public string $detail,
        public string $subCoreKey,
        public string $subCoreName,
    ) {}
}
