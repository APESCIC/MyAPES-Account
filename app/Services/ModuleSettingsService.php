<?php

namespace App\Services;

use App\Models\ModuleSetting;
use App\Models\User;
use App\Support\ModuleSettingsDefaults;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ModuleSettingsService
{
    public function supportsSettings(string $moduleKey): bool
    {
        return in_array($moduleKey, ModuleSettingsDefaults::configurableModules(), true);
    }

    /** @return array<string, mixed> */
    public function defaults(string $subCoreKey, string $moduleKey): array
    {
        return ModuleSettingsDefaults::for($subCoreKey, $moduleKey) ?? [];
    }

    public function ensureSeeded(string $subCoreKey, string $moduleKey): ?ModuleSetting
    {
        $defaults = ModuleSettingsDefaults::for($subCoreKey, $moduleKey);
        if ($defaults === null) {
            return null;
        }

        $existing = ModuleSetting::query()
            ->where('sub_core_key', $subCoreKey)
            ->where('module_key', $moduleKey)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return ModuleSetting::query()->create([
            'sub_core_key' => $subCoreKey,
            'module_key' => $moduleKey,
            'settings' => $defaults,
            'lock_version' => 1,
            'updated_by' => null,
        ]);
    }

    /** @return array<string, mixed> */
    public function get(string $subCoreKey, string $moduleKey): array
    {
        $record = $this->ensureSeeded($subCoreKey, $moduleKey);
        if ($record === null) {
            return [];
        }

        return is_array($record->settings) ? $record->settings : [];
    }

    public function record(string $subCoreKey, string $moduleKey): ?ModuleSetting
    {
        return $this->ensureSeeded($subCoreKey, $moduleKey);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function save(
        string $subCoreKey,
        string $moduleKey,
        array $settings,
        int $expectedLockVersion,
        ?User $actor = null,
    ): ModuleSetting {
        if (! $this->supportsSettings($moduleKey) || ModuleSettingsDefaults::for($subCoreKey, $moduleKey) === null) {
            throw ValidationException::withMessages([
                'module' => 'This module does not support editable settings.',
            ]);
        }

        $this->validateStructure($moduleKey, $settings);

        return DB::transaction(function () use (
            $subCoreKey,
            $moduleKey,
            $settings,
            $expectedLockVersion,
            $actor,
        ): ModuleSetting {
            $record = ModuleSetting::query()
                ->where('sub_core_key', $subCoreKey)
                ->where('module_key', $moduleKey)
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                $record = $this->ensureSeeded($subCoreKey, $moduleKey);
            }

            if ($record === null) {
                throw ValidationException::withMessages([
                    'module' => 'Settings could not be loaded.',
                ]);
            }

            if ((int) $record->lock_version !== $expectedLockVersion) {
                throw ValidationException::withMessages([
                    'version' => 'Settings were updated elsewhere. Refresh and try again.',
                ]);
            }

            $record->forceFill([
                'settings' => $settings,
                'lock_version' => $record->lock_version + 1,
                'updated_by' => $actor?->id,
            ])->save();

            return $record->fresh();
        });
    }

    public function resetToDefaults(
        string $subCoreKey,
        string $moduleKey,
        int $expectedLockVersion,
        ?User $actor = null,
    ): ModuleSetting {
        $defaults = ModuleSettingsDefaults::for($subCoreKey, $moduleKey);
        if ($defaults === null) {
            throw ValidationException::withMessages([
                'module' => 'This module does not support editable settings.',
            ]);
        }

        return $this->save($subCoreKey, $moduleKey, $defaults, $expectedLockVersion, $actor);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function validateStructure(string $moduleKey, array $settings): void
    {
        if (! isset($settings['websites']) || ! is_array($settings['websites'])) {
            throw ValidationException::withMessages([
                'settings' => 'Websites list is required.',
            ]);
        }

        $groupKey = $moduleKey === 'tickets' ? 'service_areas' : 'categories';
        if (! isset($settings[$groupKey]) || ! is_array($settings[$groupKey]) || $settings[$groupKey] === []) {
            throw ValidationException::withMessages([
                'settings' => 'At least one category group is required.',
            ]);
        }

        $websiteKeys = [];
        foreach ($settings['websites'] as $website) {
            if (! is_array($website) || ! is_string($website['key'] ?? null) || $website['key'] === '') {
                throw ValidationException::withMessages([
                    'settings' => 'Each website needs a key.',
                ]);
            }
            $websiteKeys[] = $website['key'];
        }

        if (count($websiteKeys) !== count(array_unique($websiteKeys))) {
            throw ValidationException::withMessages([
                'settings' => 'Website keys must be unique.',
            ]);
        }

        $groupKeys = [];
        foreach ($settings[$groupKey] as $group) {
            if (! is_array($group) || ! is_string($group['key'] ?? null) || $group['key'] === '') {
                throw ValidationException::withMessages([
                    'settings' => 'Each group needs a key.',
                ]);
            }
            $groupKeys[] = $group['key'];
            $subs = $group['subcategories'] ?? [];
            if (! is_array($subs) || $subs === []) {
                throw ValidationException::withMessages([
                    'settings' => "Group {$group['key']} needs at least one subcategory.",
                ]);
            }
            $subKeys = [];
            foreach ($subs as $sub) {
                if (! is_array($sub) || ! is_string($sub['key'] ?? null) || $sub['key'] === '') {
                    throw ValidationException::withMessages([
                        'settings' => 'Each subcategory needs a key.',
                    ]);
                }
                $subKeys[] = $sub['key'];
            }
            if (count($subKeys) !== count(array_unique($subKeys))) {
                throw ValidationException::withMessages([
                    'settings' => "Subcategory keys in {$group['key']} must be unique.",
                ]);
            }
        }

        if (count($groupKeys) !== count(array_unique($groupKeys))) {
            throw ValidationException::withMessages([
                'settings' => 'Category keys must be unique.',
            ]);
        }
    }

    /** Seed defaults for all configurable APES CIC modules. */
    public function seedConfigurableDefaults(): int
    {
        $created = 0;
        foreach (ModuleSettingsDefaults::configurableModules() as $moduleKey) {
            $before = ModuleSetting::query()
                ->where('sub_core_key', 'apes-cic')
                ->where('module_key', $moduleKey)
                ->exists();
            $this->ensureSeeded('apes-cic', $moduleKey);
            if (! $before) {
                $created++;
            }
        }

        return $created;
    }
}
