<?php

namespace App\Services;

use App\Contracts\ModuleRegistry;
use Illuminate\Support\Facades\DB;

class ModuleInstallationSynchronizer
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly AuthorizationMetadataSynchronizer $authorization,
    ) {}

    /** @return array{created: int, existing: int} */
    public function synchronize(): array
    {
        return DB::transaction(function (): array {
            $this->authorization->synchronize();
            $created = 0;
            $existing = 0;
            $now = now();

            foreach ($this->registry->shippedInstances() as $instance) {
                $inserted = DB::table('module_installations')->insertOrIgnore([
                    'sub_core_key' => $instance->subCore->key,
                    'module_key' => $instance->module->key,
                    'enabled' => true,
                    'installed_at' => $now,
                    'installed_by' => null,
                    'enabled_at' => $now,
                    'enabled_by' => null,
                    'disabled_at' => null,
                    'disabled_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($inserted === 1) {
                    $created++;
                } else {
                    $existing++;
                }
            }

            return compact('created', 'existing');
        });
    }
}
