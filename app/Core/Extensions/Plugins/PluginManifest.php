<?php

namespace App\Core\Extensions\Plugins;

/**
 * Package manifest for a reusable plugin (#287).
 *
 * {@see $translationNamespace} and {@see $searchKeywordsKey} feed the Language
 * line (#272 / #274) without further contract changes.
 */
final readonly class PluginManifest
{
    /**
     * @param  list<string>  $compatibleModules  Module slugs, or ['*']
     * @param  list<string>  $shippedModules  Modules where this plugin ships today
     * @param  list<PluginAbility>  $permissions
     * @param  list<PluginDependency>  $dependencies
     * @param  list<PluginNavigationItem>  $navigation
     * @param  array<string, PluginSettingsSchema>  $settingsByModule  keyed by module slug
     * @param  class-string|null  $activeRecordDetector
     * @param  class-string|null  $summaryProvider
     * @param  class-string|null  $recentActivityProvider
     * @param  class-string|null  $analyticsProvider
     * @param  class-string|null  $attentionProvider
     * @param  list<string>  $publicRouteFiles
     * @param  list<string>  $staffRouteFiles
     */
    public function __construct(
        public string $slug,
        public string $name,
        public string $description,
        public string $version,
        public string $requiresCore,
        public array $compatibleModules,
        public array $shippedModules,
        public array $permissions,
        public array $dependencies = [],
        public array $navigation = [],
        public array $settingsByModule = [],
        public string $translationNamespace = '',
        public string $searchKeywordsKey = '',
        public ?string $activeRecordDetector = null,
        public ?string $summaryProvider = null,
        public ?string $recentActivityProvider = null,
        public ?string $analyticsProvider = null,
        public ?string $attentionProvider = null,
        public ?string $migrationsPath = null,
        public array $publicRouteFiles = [],
        public array $staffRouteFiles = [],
        public ?string $nameKey = null,
        public ?string $descriptionKey = null,
    ) {}

    public static function make(
        string $slug,
        string $name,
        string $description,
        string $version,
        string $requiresCore,
        array $compatibleModules,
        array $shippedModules,
        array $permissions,
        array $dependencies = [],
        array $navigation = [],
        array $settingsByModule = [],
        ?string $translationNamespace = null,
        ?string $searchKeywordsKey = null,
        ?string $activeRecordDetector = null,
        ?string $summaryProvider = null,
        ?string $recentActivityProvider = null,
        ?string $analyticsProvider = null,
        ?string $attentionProvider = null,
        ?string $migrationsPath = null,
        array $publicRouteFiles = [],
        array $staffRouteFiles = [],
        ?string $nameKey = null,
        ?string $descriptionKey = null,
    ): self {
        $namespace = $translationNamespace ?? str_replace('-', '_', $slug);

        return new self(
            slug: $slug,
            name: $name,
            description: $description,
            version: $version,
            requiresCore: $requiresCore,
            compatibleModules: $compatibleModules,
            shippedModules: $shippedModules,
            permissions: $permissions,
            dependencies: $dependencies,
            navigation: $navigation,
            settingsByModule: $settingsByModule,
            translationNamespace: $namespace,
            searchKeywordsKey: $searchKeywordsKey ?? $namespace.'::plugin.keywords',
            activeRecordDetector: $activeRecordDetector,
            summaryProvider: $summaryProvider,
            recentActivityProvider: $recentActivityProvider,
            analyticsProvider: $analyticsProvider,
            attentionProvider: $attentionProvider,
            migrationsPath: $migrationsPath,
            publicRouteFiles: $publicRouteFiles,
            staffRouteFiles: $staffRouteFiles,
            nameKey: $nameKey,
            descriptionKey: $descriptionKey,
        );
    }

    public function settingsFor(string $moduleSlug): PluginSettingsSchema
    {
        return $this->settingsByModule[$moduleSlug]
            ?? new PluginSettingsSchema(supportsSettings: false);
    }

    public function isCompatibleWith(string $moduleSlug): bool
    {
        return in_array('*', $this->compatibleModules, true)
            || in_array($moduleSlug, $this->compatibleModules, true);
    }

    public function isShippedFor(string $moduleSlug): bool
    {
        return in_array($moduleSlug, $this->shippedModules, true);
    }
}
