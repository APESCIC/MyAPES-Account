<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class TicketCategoryResolver
{
    public function __construct(
        private readonly ModuleSettingsService $settings,
    ) {}

    /** @return array<string, mixed> */
    public function settingsFor(string $subCoreKey): array
    {
        if ($subCoreKey !== 'apes-cic') {
            return [];
        }

        return $this->settings->get($subCoreKey, 'tickets');
    }

    /**
     * @return array<int, array{key: string, label: string, subcategories: array<int, array<string, mixed>>}>
     */
    public function serviceAreas(string $subCoreKey): array
    {
        $settings = $this->settingsFor($subCoreKey);
        $areas = $settings['service_areas'] ?? [];

        return is_array($areas) ? array_values($areas) : [];
    }

    /** @return array<int, string> */
    public function serviceAreaKeys(string $subCoreKey): array
    {
        return array_map(
            static fn (array $area): string => (string) $area['key'],
            $this->serviceAreas($subCoreKey),
        );
    }

    /**
     * @return array<int, array{key: string, label: string, url: ?string}>
     */
    public function websites(string $subCoreKey): array
    {
        $settings = $this->settingsFor($subCoreKey);
        $websites = $settings['websites'] ?? [];

        return is_array($websites) ? array_values($websites) : [];
    }

    /** @return array<int, string> */
    public function websiteKeys(string $subCoreKey): array
    {
        return array_map(
            static fn (array $site): string => (string) $site['key'],
            $this->websites($subCoreKey),
        );
    }

    /** @return array<string, mixed>|null */
    public function findSubcategory(string $subCoreKey, string $serviceArea, string $subCategory): ?array
    {
        foreach ($this->serviceAreas($subCoreKey) as $area) {
            if (($area['key'] ?? null) !== $serviceArea) {
                continue;
            }
            foreach ($area['subcategories'] ?? [] as $sub) {
                if (($sub['key'] ?? null) === $subCategory) {
                    return $sub;
                }
            }
        }

        return null;
    }

    public function labelForArea(string $subCoreKey, string $key): string
    {
        foreach ($this->serviceAreas($subCoreKey) as $area) {
            if (($area['key'] ?? null) === $key) {
                return (string) ($area['label'] ?? $key);
            }
        }

        return str_replace('_', ' ', $key);
    }

    public function labelForSubcategory(string $subCoreKey, string $areaKey, string $subKey): string
    {
        $sub = $this->findSubcategory($subCoreKey, $areaKey, $subKey);

        return $sub !== null
            ? (string) ($sub['label'] ?? $subKey)
            : str_replace('_', ' ', $subKey);
    }

    public function labelForWebsite(string $subCoreKey, ?string $key): string
    {
        if ($key === null || $key === '') {
            return '—';
        }
        foreach ($this->websites($subCoreKey) as $site) {
            if (($site['key'] ?? null) === $key) {
                return (string) ($site['label'] ?? $key);
            }
        }

        return str_replace('_', ' ', $key);
    }

    /**
     * @return array{service_area: string, sub_category: string, affected_website_key: ?string}
     */
    public function validateSelection(
        string $subCoreKey,
        string $serviceArea,
        string $subCategory,
        ?string $websiteKey,
    ): array {
        $sub = $this->findSubcategory($subCoreKey, $serviceArea, $subCategory);
        if ($sub === null) {
            throw ValidationException::withMessages([
                'sub_category' => 'Choose a valid subcategory for the selected service area.',
            ]);
        }

        $requiresWebsite = (bool) ($sub['requires_website'] ?? false);
        $websiteKeys = $this->websiteKeys($subCoreKey);

        if ($requiresWebsite) {
            if ($websiteKey === null || $websiteKey === '') {
                throw ValidationException::withMessages([
                    'affected_website_key' => 'Select which website is affected.',
                ]);
            }
            if (! in_array($websiteKey, $websiteKeys, true)) {
                throw ValidationException::withMessages([
                    'affected_website_key' => 'Select a valid website.',
                ]);
            }
        } elseif ($websiteKey !== null && $websiteKey !== '' && ! in_array($websiteKey, $websiteKeys, true)) {
            throw ValidationException::withMessages([
                'affected_website_key' => 'Select a valid website.',
            ]);
        }

        return [
            'service_area' => $serviceArea,
            'sub_category' => $subCategory,
            'affected_website_key' => $websiteKey === '' ? null : $websiteKey,
        ];
    }

    public function allowsAttachments(string $subCoreKey, string $serviceArea, string $subCategory): bool
    {
        $sub = $this->findSubcategory($subCoreKey, $serviceArea, $subCategory);

        return (bool) ($sub['allows_attachments'] ?? false);
    }
}
