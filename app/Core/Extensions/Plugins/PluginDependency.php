<?php

namespace App\Core\Extensions\Plugins;

/**
 * Declared dependency on another plugin (#287 / #284–#286).
 *
 * When {@see $onlyModules} is null, the dependency applies in every module
 * where this plugin ships. When set, it applies only for those module slugs
 * (e.g. Cases → Pet Profiles only under shelter-rescue).
 */
final readonly class PluginDependency
{
    /**
     * @param  list<string>|null  $onlyModules
     */
    public function __construct(
        public string $pluginSlug,
        public string $versionConstraint = '*',
        public ?array $onlyModules = null,
    ) {}

    public function appliesTo(string $moduleSlug): bool
    {
        return $this->onlyModules === null
            || in_array($moduleSlug, $this->onlyModules, true);
    }
}
