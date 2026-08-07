<?php

namespace App\Services;

use App\Contracts\ModuleActiveRecordDetector;
use App\Contracts\ModuleRegistry;
use App\Models\ModuleInstallation;
use App\Modules\ModuleDefinition;
use App\Modules\SubCoreDefinition;

class ModuleAdministrationCatalogue
{
    public function __construct(
        private readonly ModuleRegistry $registry,
    ) {}

    /**
     * @return array{
     *     subCores: array<string, SubCoreDefinition>,
     *     modules: array<string, ModuleDefinition>,
     *     cells: array<string, array<string, mixed>>
     * }
     */
    public function matrix(): array
    {
        $installations = ModuleInstallation::query()
            ->get()
            ->keyBy->instanceKey();
        $cells = [];

        foreach ($this->registry->matrix() as $instance) {
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

            $cells[$instance->key()] = [
                'definition' => $instance,
                'installation' => $installation,
                'active_record_count' => $activeRecords,
                'dependencies' => $dependencies,
                'transition_at' => $transitionAt,
                'actor_id' => $actorId,
            ];
        }

        return [
            'subCores' => $this->registry->subCores(),
            'modules' => $this->registry->modules(),
            'cells' => $cells,
        ];
    }
}
