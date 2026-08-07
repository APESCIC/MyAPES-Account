<?php

namespace App\Modules;

final readonly class ModuleInstanceDefinition
{
    /** @param array<int, ModuleDependency> $dependencies */
    public function __construct(
        public SubCoreDefinition $subCore,
        public ModuleDefinition $module,
        public ModuleCodeStatus $codeStatus,
        public array $dependencies = [],
    ) {}

    public function key(): string
    {
        return "{$this->subCore->key}:{$this->module->key}";
    }

    /** @return array<int, string> */
    public function dependencyKeys(): array
    {
        return array_map(
            static fn (ModuleDependency $dependency): string => $dependency->key(),
            $this->dependencies,
        );
    }

    public function isShipped(): bool
    {
        return $this->codeStatus === ModuleCodeStatus::Shipped;
    }
}
