<?php

namespace App\Modules;

final readonly class ModuleNavigationItem
{
    public function __construct(
        public string $instanceKey,
        public string $subCoreKey,
        public string $moduleKey,
        public string $label,
        public string $routeName,
        public string $icon,
        public int $order,
    ) {}
}
