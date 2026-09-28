<?php

namespace App\Core\Extensions\Plugins;

use App\Contracts\ModuleLifecycleManager;
use App\Contracts\ModuleRegistry;
use App\Core\Accounts\User;
use App\Core\Extensions\Models\ModuleInstallation;
use App\Services\ModuleState;
use InvalidArgumentException;

/**
 * Core façade for per-module plugin enablement (#288).
 *
 * Delegates transitions to {@see ModuleLifecycleManager} so lock / dependency /
 * active-record behaviour stays unchanged.
 */
final class PluginEnablement
{
    public function __construct(
        private readonly ModuleState $state,
        private readonly ModuleLifecycleManager $lifecycle,
        private readonly ModuleRegistry $registry,
    ) {}

    public function isEnabled(string $module, string $plugin): bool
    {
        return $this->state->enabled($module, $plugin);
    }

    public function assertEnabled(string $module, string $plugin): void
    {
        $this->state->assertEnabled($module, $plugin);
    }

    public function enable(
        User $actor,
        string $module,
        string $plugin,
        int $expectedVersion,
    ): ModuleInstallation {
        return $this->lifecycle->enable($actor, $module, $plugin, $expectedVersion);
    }

    public function disable(
        User $actor,
        string $module,
        string $plugin,
        int $expectedVersion,
    ): ModuleInstallation {
        return $this->lifecycle->disable($actor, $module, $plugin, $expectedVersion);
    }

    public function install(
        User $actor,
        string $module,
        string $plugin,
    ): ModuleInstallation {
        return $this->lifecycle->install($actor, $module, $plugin);
    }

    /**
     * @return list<string> Enabled instance keys as "{module}:{plugin}".
     */
    public function enabledInstanceKeys(): array
    {
        return ModuleInstallation::query()
            ->where('enabled', true)
            ->orderBy('sub_core_key')
            ->orderBy('module_key')
            ->get()
            ->map->instanceKey()
            ->all();
    }

    /**
     * Whether a permission name belongs to an enabled module×plugin.
     *
     * Grants are kept when a plugin is disabled; Access / job-role editors
     * simply stop offering the permission names.
     */
    public function isOfferedInAccessEditors(string $permissionName): bool
    {
        if (preg_match(
            '/^(?<module>[a-z][a-z0-9]*(?:-[a-z0-9]+)*)\.(?<plugin>[a-z][a-z0-9]*(?:-[a-z0-9]+)*)\./D',
            $permissionName,
            $matches,
        ) !== 1) {
            return true;
        }

        $module = $matches['module'];
        $plugin = $matches['plugin'];

        try {
            $this->registry->instance($module, $plugin);
        } catch (InvalidArgumentException) {
            return true;
        }

        return $this->isEnabled($module, $plugin);
    }
}
