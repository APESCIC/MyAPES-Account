<?php

namespace App\Core\Extensions\Plugins;

/**
 * Staff or public navigation item contributed by a plugin (#287).
 */
final readonly class PluginNavigationItem
{
    public function __construct(
        public string $label,
        public string $routeName,
        public string $icon,
        public int $order,
        public ?string $requiredAbility = null,
        public bool $public = false,
        public ?string $labelKey = null,
        public ?string $moduleSlug = null,
    ) {}
}
