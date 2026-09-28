<?php

namespace App\Services;

use App\Core\Extensions\Modules\ModulePackageRegistry;
use App\Core\Extensions\Plugins\PluginRegistry;
use App\Core\Extensions\Plugins\PluginSettingsSchema;
use App\Modules\ModuleSettingsDescriptor;
use App\Support\ModuleSettingsDefaults;
use InvalidArgumentException;

/**
 * Single source of truth for per-plugin settings metadata and defaults.
 *
 * Built from plugin manifests (#287). Keeps the ModuleSettingsDescriptor
 * shape for Admin Plugins pages until enablement moves (#288).
 */
final class ModuleSettingsRegistry
{
    private const PLUGIN_ORDER = [
        'tickets' => 10,
        'cases' => 20,
        'recruitment' => 30,
        'pet-profiles' => 40,
        'consultations' => 50,
    ];

    public function __construct(
        private readonly PluginRegistry $plugins,
        private readonly ModulePackageRegistry $modules,
    ) {}

    /**
     * @return list<ModuleSettingsDescriptor>
     */
    public function descriptors(): array
    {
        $descriptors = [];

        foreach ($this->plugins->plugins() as $plugin) {
            foreach ($plugin->shippedModules as $moduleSlug) {
                $schema = $plugin->settingsFor($moduleSlug);
                $descriptors[] = $this->toDescriptor($moduleSlug, $plugin->slug, $schema);
            }
        }

        $moduleOrder = [];
        foreach (array_values($this->modules->modules()) as $index => $manifest) {
            $moduleOrder[$manifest->slug] = $manifest->sortOrder;
        }

        usort(
            $descriptors,
            static function (ModuleSettingsDescriptor $left, ModuleSettingsDescriptor $right) use ($moduleOrder): int {
                $byModule = ($moduleOrder[$left->subCoreKey] ?? 999)
                    <=> ($moduleOrder[$right->subCoreKey] ?? 999);

                if ($byModule !== 0) {
                    return $byModule;
                }

                return (self::PLUGIN_ORDER[$left->moduleKey] ?? 99)
                    <=> (self::PLUGIN_ORDER[$right->moduleKey] ?? 99);
            },
        );

        return $descriptors;
    }

    public function descriptor(string $subCoreKey, string $moduleKey): ModuleSettingsDescriptor
    {
        foreach ($this->descriptors() as $descriptor) {
            if ($descriptor->subCoreKey === $subCoreKey && $descriptor->moduleKey === $moduleKey) {
                return $descriptor;
            }
        }

        return new ModuleSettingsDescriptor(
            subCoreKey: $subCoreKey,
            moduleKey: $moduleKey,
            supportsSettings: false,
        );
    }

    public function supportsSettings(string $subCoreKey, string $moduleKey): bool
    {
        return $this->descriptor($subCoreKey, $moduleKey)->supportsSettings;
    }

    /**
     * @return list<string>
     */
    public function configurableModuleKeys(string $subCoreKey = 'apes-cic'): array
    {
        $keys = [];
        foreach ($this->descriptors() as $descriptor) {
            if ($descriptor->subCoreKey === $subCoreKey && $descriptor->supportsSettings) {
                $keys[] = $descriptor->moduleKey;
            }
        }

        return $keys;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function defaults(string $subCoreKey, string $moduleKey): ?array
    {
        if (! $this->supportsSettings($subCoreKey, $moduleKey)) {
            return null;
        }

        return match ($moduleKey) {
            'tickets' => $subCoreKey === 'apes-cic' ? ModuleSettingsDefaults::ticketsForApesCic() : null,
            'cases' => $subCoreKey === 'apes-cic' ? ModuleSettingsDefaults::casesForApesCic() : null,
            'recruitment' => $subCoreKey === 'apes-cic' ? ModuleSettingsDefaults::recruitmentForApesCic() : null,
            default => throw new InvalidArgumentException("No defaults for configurable module [{$moduleKey}]."),
        };
    }

    private function toDescriptor(
        string $moduleSlug,
        string $pluginSlug,
        PluginSettingsSchema $schema,
    ): ModuleSettingsDescriptor {
        return new ModuleSettingsDescriptor(
            subCoreKey: $moduleSlug,
            moduleKey: $pluginSlug,
            supportsSettings: $schema->supportsSettings,
            schema: $schema->schema,
            settingsRouteName: $schema->settingsRouteName,
            viewPermission: $schema->viewPermission,
            managePermission: $schema->managePermission,
            navLabel: $schema->navLabel,
            groupKey: $schema->groupKey,
            groupLabel: $schema->groupLabel,
        );
    }
}
