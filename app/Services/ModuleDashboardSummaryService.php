<?php

namespace App\Services;

use App\Contracts\ModuleAggregateSummaryProvider;
use App\Contracts\ModuleNavigationProvider;
use App\Contracts\ModuleRegistry;
use App\Models\User;
use App\Modules\ModuleSummary;

class ModuleDashboardSummaryService
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly ModuleNavigationProvider $navigation,
    ) {}

    /** @return array<int, ModuleSummary> */
    public function forUser(User $user): array
    {
        $visible = [];
        foreach ($this->navigation->forUser($user) as $subCore) {
            foreach ($subCore->modules as $module) {
                $visible[] = $module->instanceKey;
            }
        }

        $summaries = [];
        foreach ($visible as $key) {
            [$subCoreKey, $moduleKey] = explode(':', $key, 2);
            $instance = $this->registry->instance($subCoreKey, $moduleKey);
            $providerClass = $instance->module->summaryProvider;

            if ($providerClass === null) {
                continue;
            }

            /** @var ModuleAggregateSummaryProvider $provider */
            $provider = app($providerClass);
            $summaries[] = $provider->summarize($instance, $user);
        }

        return $summaries;
    }
}
