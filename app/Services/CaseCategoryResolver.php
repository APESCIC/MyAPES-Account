<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class CaseCategoryResolver
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

        return $this->settings->get($subCoreKey, 'cases');
    }

    /**
     * @return array<int, array{key: string, label: string, subcategories: array<int, array<string, mixed>>}>
     */
    public function categories(string $subCoreKey): array
    {
        $settings = $this->settingsFor($subCoreKey);
        $categories = $settings['categories'] ?? [];

        return is_array($categories) ? array_values($categories) : [];
    }

    /** @return array<int, string> */
    public function categoryKeys(string $subCoreKey): array
    {
        return array_map(
            static fn (array $category): string => (string) $category['key'],
            $this->categories($subCoreKey),
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
    public function findSubcategory(string $subCoreKey, string $category, string $subCategory): ?array
    {
        foreach ($this->categories($subCoreKey) as $group) {
            if (($group['key'] ?? null) !== $category) {
                continue;
            }
            foreach ($group['subcategories'] ?? [] as $sub) {
                if (($sub['key'] ?? null) === $subCategory) {
                    return $sub;
                }
            }
        }

        return null;
    }

    public function labelForCategory(string $subCoreKey, string $key): string
    {
        foreach ($this->categories($subCoreKey) as $category) {
            if (($category['key'] ?? null) === $key) {
                return (string) ($category['label'] ?? $key);
            }
        }

        return str_replace('_', ' ', $key);
    }

    public function labelForSubcategory(string $subCoreKey, string $categoryKey, string $subKey): string
    {
        $sub = $this->findSubcategory($subCoreKey, $categoryKey, $subKey);

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
     * @return array{category: string, sub_category: string, affected_website_key: ?string}
     */
    public function validateSelection(
        string $subCoreKey,
        string $category,
        string $subCategory,
        ?string $websiteKey,
    ): array {
        $sub = $this->findSubcategory($subCoreKey, $category, $subCategory);
        if ($sub === null) {
            throw ValidationException::withMessages([
                'sub_category' => 'Choose a valid subcategory for the selected category.',
            ]);
        }

        $requiresWebsite = (bool) ($sub['requires_website'] ?? false);
        $websiteKeys = $this->websiteKeys($subCoreKey);

        if ($requiresWebsite) {
            if ($websiteKey === null || $websiteKey === '') {
                throw ValidationException::withMessages([
                    'affected_website_key' => 'Select which website or system is involved.',
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
            'category' => $category,
            'sub_category' => $subCategory,
            'affected_website_key' => $websiteKey === '' ? null : $websiteKey,
        ];
    }

    public function allowsAttachments(string $subCoreKey, string $category, string $subCategory): bool
    {
        $sub = $this->findSubcategory($subCoreKey, $category, $subCategory);

        return (bool) ($sub['allows_attachments'] ?? false);
    }
}
