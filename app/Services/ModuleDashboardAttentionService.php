<?php

namespace App\Services;

use App\Contracts\ModuleAttentionProvider;
use App\Contracts\ModuleNavigationProvider;
use App\Contracts\ModuleRegistry;
use App\Models\User;
use App\Modules\ModuleAttentionItem;

class ModuleDashboardAttentionService
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly ModuleNavigationProvider $navigation,
    ) {}

    /** @return array<int, ModuleAttentionItem> */
    public function forUser(User $user, int $limit = 6): array
    {
        $modules = [];

        foreach ($this->navigation->forUser($user) as $subCore) {
            array_push($modules, ...$subCore->modules);
        }

        return $this->collectForModules($user, $modules, $limit);
    }

    /** @return array<int, ModuleAttentionItem> */
    public function forUserInSubCore(
        User $user,
        string $subCoreKey,
        int $limit = 6,
    ): array {
        return $this->collectForModules(
            $user,
            $this->navigation->forSubCore($user, $subCoreKey),
            $limit,
        );
    }

    /**
     * @param  array<int, \App\Modules\ModuleNavigationItem>  $modules
     * @return array<int, ModuleAttentionItem>
     */
    private function collectForModules(User $user, array $modules, int $limit): array
    {
        $items = [];

        foreach ($modules as $module) {
            [$subCoreKey, $moduleKey] = explode(
                ':',
                $module->instanceKey,
                2,
            );
            $instance = $this->registry->instance(
                $subCoreKey,
                $moduleKey,
            );
            $providerClass = $instance->attentionProviderClass();

            if ($providerClass === null) {
                continue;
            }

            /** @var ModuleAttentionProvider $provider */
            $provider = app($providerClass);
            array_push(
                $items,
                ...$provider->attention($instance, $user, $limit),
            );
        }

        usort(
            $items,
            static fn (ModuleAttentionItem $left, ModuleAttentionItem $right): int => $right->updatedAt->getTimestamp()
                <=> $left->updatedAt->getTimestamp(),
        );

        return array_slice($items, 0, max(0, $limit));
    }
}
