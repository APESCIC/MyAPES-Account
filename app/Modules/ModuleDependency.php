<?php

namespace App\Modules;

final readonly class ModuleDependency
{
    public function __construct(
        public string $subCoreKey,
        public string $moduleKey,
    ) {}

    public function key(): string
    {
        return "{$this->subCoreKey}:{$this->moduleKey}";
    }
}
