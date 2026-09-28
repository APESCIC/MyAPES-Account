<?php

namespace App\Core\Extensions\Navigation;

/**
 * Staff primary-nav contribution from a plugin (e.g. Recruit manage) (#282).
 */
final readonly class StaffPluginNavigationItem
{
    /**
     * @param  list<string>  $abilities  any-of permission check
     */
    public function __construct(
        public string $label,
        public string $icon,
        public int $order,
        public string $routeIsPattern,
        public array $abilities,
        public bool $enabled,
        public string $homeRouteName,
        public ?string $fallbackRouteName = null,
        public ?string $homeAbility = null,
    ) {}
}
