<?php

namespace App\Modules;

final readonly class SubCoreNavigation
{
    /** @param array<int, ModuleNavigationItem> $modules */
    public function __construct(
        public SubCoreDefinition $subCore,
        public array $modules,
    ) {}
}
