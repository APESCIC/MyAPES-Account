<?php

namespace App\Core\Extensions\Plugins;

/**
 * Declared dependency on another plugin (#287).
 */
final readonly class PluginDependency
{
    public function __construct(
        public string $pluginSlug,
        public string $versionConstraint = '*',
    ) {}
}
