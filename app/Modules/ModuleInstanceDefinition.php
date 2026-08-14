<?php

namespace App\Modules;

final readonly class ModuleInstanceDefinition
{
    /**
     * @param  array<int, ModuleDependency>  $dependencies
     * @param  class-string|null  $summaryProvider
     * @param  class-string|null  $recentActivityProvider
     * @param  class-string|null  $analyticsProvider
     * @param  class-string|null  $attentionProvider
     */
    public function __construct(
        public SubCoreDefinition $subCore,
        public ModuleDefinition $module,
        public ModuleCodeStatus $codeStatus,
        public array $dependencies = [],
        public ?string $summaryProvider = null,
        public ?string $recentActivityProvider = null,
        public ?string $analyticsProvider = null,
        public ?string $attentionProvider = null,
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

    /** @return class-string|null */
    public function summaryProviderClass(): ?string
    {
        return $this->summaryProvider ?? $this->module->summaryProvider;
    }

    /** @return class-string|null */
    public function recentActivityProviderClass(): ?string
    {
        return $this->recentActivityProvider
            ?? $this->module->recentActivityProvider;
    }

    /** @return class-string|null */
    public function analyticsProviderClass(): ?string
    {
        return $this->analyticsProvider ?? $this->module->analyticsProvider;
    }

    /** @return class-string|null */
    public function attentionProviderClass(): ?string
    {
        return $this->attentionProvider ?? $this->module->attentionProvider;
    }
}
