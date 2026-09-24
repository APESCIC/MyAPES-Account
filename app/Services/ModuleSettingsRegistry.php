<?php

namespace App\Services;

use App\Modules\ModuleSettingsDescriptor;
use App\Support\ModuleSettingsDefaults;
use InvalidArgumentException;

/**
 * Single source of truth for per-plugin settings metadata and defaults.
 *
 * Admin Plugins index, ModuleSettingsService, and AdminModuleController
 * consume this registry instead of hardcoding module keys in Blade or controllers.
 */
final class ModuleSettingsRegistry
{
    /**
     * @return list<ModuleSettingsDescriptor>
     */
    public function descriptors(): array
    {
        return [
            new ModuleSettingsDescriptor(
                subCoreKey: 'apes-cic',
                moduleKey: 'tickets',
                supportsSettings: true,
                schema: ModuleSettingsDescriptor::SCHEMA_WEBSITES_CATEGORIES,
                groupKey: 'service_areas',
                groupLabel: 'Service areas',
            ),
            new ModuleSettingsDescriptor(
                subCoreKey: 'apes-cic',
                moduleKey: 'cases',
                supportsSettings: true,
                schema: ModuleSettingsDescriptor::SCHEMA_WEBSITES_CATEGORIES,
                groupKey: 'categories',
                groupLabel: 'Case categories',
            ),
            new ModuleSettingsDescriptor(
                subCoreKey: 'apes-cic',
                moduleKey: 'recruitment',
                supportsSettings: true,
                schema: ModuleSettingsDescriptor::SCHEMA_RECRUITMENT_BOARD,
            ),
            new ModuleSettingsDescriptor(
                subCoreKey: 'shelter-rescue',
                moduleKey: 'pet-profiles',
                supportsSettings: false,
            ),
            new ModuleSettingsDescriptor(
                subCoreKey: 'shelter-rescue',
                moduleKey: 'cases',
                supportsSettings: false,
            ),
            new ModuleSettingsDescriptor(
                subCoreKey: 'shelter-rescue',
                moduleKey: 'tickets',
                supportsSettings: false,
            ),
            new ModuleSettingsDescriptor(
                subCoreKey: 'pet-care-clinic',
                moduleKey: 'pet-profiles',
                supportsSettings: false,
            ),
            new ModuleSettingsDescriptor(
                subCoreKey: 'pet-care-clinic',
                moduleKey: 'consultations',
                supportsSettings: false,
            ),
            new ModuleSettingsDescriptor(
                subCoreKey: 'pet-care-clinic',
                moduleKey: 'tickets',
                supportsSettings: false,
            ),
        ];
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
}
