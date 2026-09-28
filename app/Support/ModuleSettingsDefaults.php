<?php

namespace App\Support;

use App\Services\ModuleSettingsRegistry;

/**
 * Compatibility façade for settings defaults.
 *
 * Defaults live on module packages (`modules/<slug>/config/plugin-defaults.php`)
 * and are resolved through {@see ModuleSettingsRegistry} (#284).
 */
final class ModuleSettingsDefaults
{
    /** @return array<string, mixed>|null */
    public static function for(string $subCoreKey, string $moduleKey): ?array
    {
        return app(ModuleSettingsRegistry::class)->defaults($subCoreKey, $moduleKey);
    }

    /** @return array<int, string> */
    public static function configurableModules(): array
    {
        return app(ModuleSettingsRegistry::class)->configurableModuleKeys('apes-cic');
    }
}
