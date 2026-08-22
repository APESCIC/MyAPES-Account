<?php

namespace App\Services;

use App\Contracts\ModuleAggregateSummaryProvider;
use App\Contracts\ModuleNavigationProvider;
use App\Contracts\ModuleRegistry;
use App\Models\User;
use App\Modules\ModuleSummaryGroup;

class ModuleDashboardSummaryService
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly ModuleNavigationProvider $navigation,
    ) {}

    /** @return list<ModuleSummaryGroup> */
    public function forUser(User $user): array
    {
        $groups = [];

        foreach ($this->navigation->forUser($user) as $subCoreNavigation) {
            $summaries = [];

            foreach ($subCoreNavigation->modules as $module) {
                $instance = $this->registry->instance(
                    $subCoreNavigation->subCore->key,
                    $module->moduleKey,
                );
                $providerClass = $instance->summaryProviderClass();

                if ($providerClass === null) {
                    continue;
                }

                /** @var ModuleAggregateSummaryProvider $provider */
                $provider = app($providerClass);
                $summaries[] = $provider->summarize($instance, $user);
            }

            if ($summaries === []) {
                continue;
            }

            $groups[] = new ModuleSummaryGroup(
                $subCoreNavigation->subCore->key,
                $subCoreNavigation->subCore->name,
                $subCoreNavigation->subCore->routeName,
                $summaries,
            );
        }

        return $groups;
    }

    public function groupForUserInSubCore(
        User $user,
        string $subCoreKey,
    ): ?ModuleSummaryGroup {
        foreach ($this->forUser($user) as $group) {
            if ($group->key === $subCoreKey) {
                return $group;
            }
        }

        return null;
    }
}
