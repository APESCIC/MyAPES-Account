<?php

namespace App\Modules;

final readonly class ModuleNavigationDefinition
{
    public function __construct(
        public string $label,
        public string $routeName,
        public string $icon,
        public int $order,
    ) {}
}
