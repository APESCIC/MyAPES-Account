<?php

namespace App\Services;

use App\Contracts\ModuleRegistry;
use App\Core\Extensions\Models\ModuleInstallation;
use App\Exceptions\ModuleLifecycleException;

class ModuleState
{
    public function __construct(
        private readonly ModuleRegistry $registry,
    ) {}

    public function enabled(
        string $subCoreKey,
        string $moduleKey,
    ): bool {
        try {
            $instance = $this->registry->instance($subCoreKey, $moduleKey);
        } catch (\InvalidArgumentException) {
            return false;
        }

        return $instance->isShipped()
            && ModuleInstallation::query()
                ->where('sub_core_key', $subCoreKey)
                ->where('module_key', $moduleKey)
                ->where('enabled', true)
                ->exists();
    }

    public function assertEnabled(
        string $subCoreKey,
        string $moduleKey,
    ): void {
        if (! $this->enabled($subCoreKey, $moduleKey)) {
            throw new ModuleLifecycleException('module_unavailable');
        }
    }
}
