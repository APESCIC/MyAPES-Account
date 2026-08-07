<?php

namespace App\Services;

use App\Contracts\ModuleRegistry;
use App\Exceptions\ModuleLifecycleException;
use App\Models\ModuleInstallation;
use App\Models\Permission;
use Illuminate\Support\Facades\Schema;

class ModuleIntegrityChecker
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly AuthorizationProfile $authorization,
    ) {}

    /** @return array{installations: int, permissions: int} */
    public function check(): array
    {
        if (! Schema::hasTable('module_installations')) {
            throw new ModuleLifecycleException('module_schema');
        }

        $installations = ModuleInstallation::query()
            ->orderBy('sub_core_key')
            ->orderBy('module_key')
            ->get();
        $byKey = $installations->keyBy->instanceKey();

        foreach ($installations as $installation) {
            try {
                $definition = $this->registry->instance(
                    $installation->sub_core_key,
                    $installation->module_key,
                );
            } catch (\InvalidArgumentException) {
                throw new ModuleLifecycleException('unknown_installation');
            }

            if (! $definition->isShipped()) {
                throw new ModuleLifecycleException('unshipped_installation');
            }

            if (! $installation->enabled) {
                continue;
            }

            foreach ($definition->dependencyKeys() as $dependency) {
                if (! $byKey->get($dependency)?->enabled) {
                    throw new ModuleLifecycleException('dependency_state');
                }
            }
        }

        foreach (array_keys($this->registry->shippedInstances()) as $required) {
            if (! $byKey->has($required)) {
                throw new ModuleLifecycleException('missing_installation');
            }
        }

        $expectedPermissions = array_map(
            static fn ($permission): string => $permission->name,
            $this->registry->permissions(),
        );
        $actualPermissions = Permission::query()
            ->where('guard_name', 'web')
            ->where('is_code_owned', true)
            ->whereIn('name', $expectedPermissions)
            ->pluck('name')
            ->all();

        if (count($actualPermissions) !== count($expectedPermissions)
            || array_diff($expectedPermissions, $this->authorization->permissions()) !== []) {
            throw new ModuleLifecycleException('module_permissions');
        }

        return [
            'installations' => $installations->count(),
            'permissions' => count($expectedPermissions),
        ];
    }
}
