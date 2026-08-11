<?php

namespace App\Services;

use App\Contracts\ModuleActiveRecordDetector;
use App\Contracts\ModuleAggregateSummaryProvider;
use App\Contracts\ModuleAnalyticsProvider;
use App\Contracts\ModuleRecentActivityProvider;
use App\Modules\ModuleCodeStatus;
use App\Modules\ModuleDefinition;
use App\Modules\ModuleInstanceDefinition;
use App\Modules\SubCoreDefinition;
use InvalidArgumentException;

final class ModuleRegistryValidator
{
    /**
     * @param  array<string, SubCoreDefinition>  $subCores
     * @param  array<string, ModuleDefinition>  $modules
     * @param  array<string, ModuleInstanceDefinition>  $matrix
     */
    public function validate(
        array $subCores,
        array $modules,
        array $matrix,
    ): void {
        if (count($matrix) !== count($subCores) * count($modules)) {
            throw new InvalidArgumentException(
                'Module compatibility matrix is incomplete.',
            );
        }

        foreach ($subCores as $key => $subCore) {
            if ($key !== $subCore->key) {
                throw new InvalidArgumentException(
                    'Sub-core registry key is invalid.',
                );
            }
        }

        foreach ($modules as $key => $module) {
            if ($key !== $module->key
                || array_diff($module->compatibleSubCores, array_keys($subCores)) !== []
                || array_diff($module->shippedSubCores, $module->compatibleSubCores) !== []) {
                throw new InvalidArgumentException(
                    'Module compatibility definition is invalid.',
                );
            }

            if (! is_a(
                $module->activeRecordDetector,
                ModuleActiveRecordDetector::class,
                true,
            )) {
                throw new InvalidArgumentException(
                    'Module active-record detector is invalid.',
                );
            }

            if ($module->summaryProvider !== null
                && ! is_a(
                    $module->summaryProvider,
                    ModuleAggregateSummaryProvider::class,
                    true,
                )) {
                throw new InvalidArgumentException(
                    'Module summary provider is invalid.',
                );
            }

            if ($module->recentActivityProvider !== null
                && ! is_a(
                    $module->recentActivityProvider,
                    ModuleRecentActivityProvider::class,
                    true,
                )) {
                throw new InvalidArgumentException(
                    'Module recent-activity provider is invalid.',
                );
            }

            if ($module->analyticsProvider !== null
                && ! is_a(
                    $module->analyticsProvider,
                    ModuleAnalyticsProvider::class,
                    true,
                )) {
                throw new InvalidArgumentException(
                    'Module analytics provider is invalid.',
                );
            }
        }

        foreach ($subCores as $subCore) {
            foreach ($modules as $module) {
                $key = "{$subCore->key}:{$module->key}";
                $instance = $matrix[$key] ?? null;
                $compatible = in_array(
                    $subCore->key,
                    $module->compatibleSubCores,
                    true,
                );
                $shipped = in_array(
                    $subCore->key,
                    $module->shippedSubCores,
                    true,
                );
                $expectedStatus = $shipped
                    ? ModuleCodeStatus::Shipped
                    : ($compatible
                        ? ModuleCodeStatus::CodeNotShipped
                        : ModuleCodeStatus::Incompatible);

                if (! $instance instanceof ModuleInstanceDefinition
                    || $instance->key() !== $key
                    || $instance->subCore->key !== $subCore->key
                    || $instance->module->key !== $module->key
                    || $instance->codeStatus !== $expectedStatus) {
                    throw new InvalidArgumentException(
                        'Module compatibility matrix is invalid.',
                    );
                }
            }
        }

        foreach ($matrix as $instance) {
            if (! $instance->isShipped()
                && $instance->dependencies !== []) {
                throw new InvalidArgumentException(
                    'Unavailable module instances cannot declare dependencies.',
                );
            }

            foreach ($instance->dependencies as $dependency) {
                $target = $matrix[$dependency->key()] ?? null;

                if (! $target instanceof ModuleInstanceDefinition
                    || ! $target->isShipped()
                    || $dependency->key() === $instance->key()) {
                    throw new InvalidArgumentException(
                        'Module dependency is unavailable.',
                    );
                }
            }
        }

        $this->assertAcyclicDependencies($matrix);
    }

    /** @param array<string, ModuleInstanceDefinition> $matrix */
    private function assertAcyclicDependencies(array $matrix): void
    {
        $visiting = [];
        $visited = [];

        $visit = function (string $key) use (
            &$visit,
            &$visiting,
            &$visited,
            $matrix,
        ): void {
            if (isset($visiting[$key])) {
                throw new InvalidArgumentException(
                    'Module dependency cycle detected.',
                );
            }

            if (isset($visited[$key])) {
                return;
            }

            $visiting[$key] = true;

            foreach ($matrix[$key]->dependencyKeys() as $dependency) {
                $visit($dependency);
            }

            unset($visiting[$key]);
            $visited[$key] = true;
        };

        foreach ($matrix as $key => $instance) {
            if ($instance->isShipped()) {
                $visit($key);
            }
        }
    }
}
