<?php

namespace App\Core\Extensions\Modules;

/**
 * Package manifest for an organisation-area module (#283).
 */
final readonly class ModuleManifest
{
    /**
     * @param  list<string>  $plugins  Plugin slugs this module composes by default
     */
    public function __construct(
        public string $slug,
        public string $name,
        public string $description,
        public string $routePrefix,
        public string $routeNamePrefix,
        public string $hubRouteName,
        public string $icon,
        public int $sortOrder,
        public bool $enabledByDefault = true,
        public ?string $nameKey = null,
        public ?string $descriptionKey = null,
        public ?ModuleStaffArea $staffArea = null,
        public array $plugins = [],
    ) {}

    /**
     * Compatibility shape for the legacy SubCoreDefinition adapter.
     */
    public function basePath(): string
    {
        return $this->routePrefix;
    }
}
