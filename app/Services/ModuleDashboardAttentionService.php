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
        $items = [];

        foreach ($this->navigation->forUser($user) as $subCore) {
            foreach ($subCore->modules as $module) {
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
        }

        usort(
            $items,
            static fn (ModuleAttentionItem $left, ModuleAttentionItem $right): int => $right->updatedAt->getTimestamp()
                <=> $left->updatedAt->getTimestamp(),
        );

        return array_slice($items, 0, max(0, $limit));
    }
}
