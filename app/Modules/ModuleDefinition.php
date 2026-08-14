<?php

namespace App\Modules;

final readonly class ModuleDefinition
{
    /**
     * @param  array<int, string>  $compatibleSubCores
     * @param  array<int, string>  $shippedSubCores
     * @param  array<int, ModuleAbilityDefinition>  $abilities
     * @param  array<string, ModuleNavigationDefinition>  $navigation
     * @param  class-string  $activeRecordDetector
     * @param  class-string|null  $summaryProvider
     * @param  class-string|null  $recentActivityProvider
     * @param  class-string|null  $analyticsProvider
     * @param  class-string|null  $attentionProvider
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $description,
        public string $version,
        public array $compatibleSubCores,
        public array $shippedSubCores,
        public array $abilities,
        public array $navigation,
        public string $activeRecordDetector,
        public ?string $summaryProvider = null,
        public ?string $recentActivityProvider = null,
        public ?string $analyticsProvider = null,
        public ?string $attentionProvider = null,
    ) {}
}
