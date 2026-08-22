<?php

namespace App\Services;

use App\Models\ModuleSetting;
use App\Support\ModuleSettingsDefaults;
use Illuminate\Support\Facades\DB;

class ModuleSettingsSynchronizer
{
    public function __construct(
        private readonly ModuleSettingsService $settings,
    ) {}

    /** @return array{created: int, existing: int} */
    public function synchronize(): array
    {
        return DB::transaction(function (): array {
            $created = 0;
            $existing = 0;

            foreach (['apes-cic'] as $subCoreKey) {
                foreach (ModuleSettingsDefaults::configurableModules() as $moduleKey) {
                    $defaults = ModuleSettingsDefaults::for($subCoreKey, $moduleKey);
                    if ($defaults === null) {
                        continue;
                    }

                    $record = ModuleSetting::query()
                        ->where('sub_core_key', $subCoreKey)
                        ->where('module_key', $moduleKey)
                        ->lockForUpdate()
                        ->first();

                    if ($record !== null) {
                        $existing++;

                        continue;
                    }

                    ModuleSetting::create([
                        'sub_core_key' => $subCoreKey,
                        'module_key' => $moduleKey,
                        'settings' => $defaults,
                        'lock_version' => 1,
                        'updated_by' => null,
                    ]);
                    $created++;
                }
            }

            return compact('created', 'existing');
        });
    }
}
