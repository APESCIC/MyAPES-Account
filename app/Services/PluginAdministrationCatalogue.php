<?php

namespace App\Services;

use App\Contracts\ModuleActiveRecordDetector;
use App\Contracts\ModuleRegistry;
use App\Core\Extensions\Models\ModuleInstallation;
use App\Core\Extensions\Plugins\PluginRegistry;
use App\Modules\ModuleDefinition;
use App\Modules\SubCoreDefinition;

/**
 * Admin → Plugins catalogue grouped by plugin (#288).
 */
class PluginAdministrationCatalogue
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly PluginRegistry $plugins,
        private readonly ModuleSettingsRegistry $settingsRegistry,
    ) {}

    /**
     * @return array{
     *     plugins: list<array{
     *         definition: ModuleDefinition,
     *         manifest_version: string,
     *         compatible_modules: list<string>,
     *         dependencies: list<string>,
     *         cells: list<array<string, mixed>>
     *     }>,
     *     subCores: array<string, SubCoreDefinition>
     * }
     */
    public function byPlugin(): array
    {
        $installations = ModuleInstallation::query()
            ->get()
            ->keyBy->instanceKey();
        $rows = [];

        foreach ($this->registry->modules() as $moduleDef) {
            $manifest = $this->plugins->has($moduleDef->key)
                ? $this->plugins->plugin($moduleDef->key)
                : null;
            $cells = [];

            foreach ($this->registry->subCores() as $subCore) {
                try {
                    $instance = $this->registry->instance($subCore->key, $moduleDef->key);
                } catch (\InvalidArgumentException) {
                    continue;
                }

                $installation = $installations->get($instance->key());
                $activeRecords = null;
                $dependencies = [];

                if ($instance->isShipped()) {
                    /** @var ModuleActiveRecordDetector $detector */
                    $detector = app($instance->module->activeRecordDetector);
                    $activeRecords = $detector->count($instance);

                    foreach ($instance->dependencyKeys() as $dependencyKey) {
                        $dependency = $installations->get($dependencyKey);
                        $dependencies[] = [
                            'key' => $dependencyKey,
                            'enabled' => $dependency?->enabled === true,
                        ];
                    }
                }

                $transitionAt = null;
                $actorId = null;
                if ($installation instanceof ModuleInstallation) {
                    if ($installation->enabled) {
                        $transitionAt = $installation->enabled_at;
                        $actorId = $installation->enabled_by;
                    } else {
                        $transitionAt = $installation->disabled_at;
                        $actorId = $installation->disabled_by;
                    }
                }

                $cells[] = [
                    'sub_core' => $subCore,
                    'definition' => $instance,
                    'installation' => $installation,
                    'active_record_count' => $activeRecords,
                    'dependencies' => $dependencies,
                    'transition_at' => $transitionAt,
                    'actor_id' => $actorId,
                    'settings' => $this->settingsRegistry->descriptor(
                        $subCore->key,
                        $moduleDef->key,
                    ),
                ];
            }

            $rows[] = [
                'definition' => $moduleDef,
                'manifest_version' => $manifest?->version ?? $moduleDef->version,
                'compatible_modules' => $manifest?->compatibleModules ?? array_keys($this->registry->subCores()),
                'dependencies' => array_map(
                    static fn ($dep): string => $dep->pluginSlug,
                    $manifest?->dependencies ?? [],
                ),
                'cells' => $cells,
            ];
        }

        usort(
            $rows,
            static fn (array $left, array $right): int => $left['definition']->key
                <=> $right['definition']->key,
        );

        return [
            'plugins' => $rows,
            'subCores' => $this->registry->subCores(),
        ];
    }
}
