<?php

namespace App\Core\Extensions\Navigation;

/**
 * Public (guest / signed-in) primary-nav contribution from a plugin (#282 / #287).
 */
final readonly class PublicNavigationItem
{
    public function __construct(
        public string $label,
        public string $routeName,
        public string $icon,
        public int $order,
        public string $routeIsPattern,
        public bool $enabled,
    ) {}
}
