<?php

namespace App\Core\Extensions\Plugins;

/**
 * Permission ability declared by a plugin manifest (#287).
 */
final readonly class PluginAbility
{
    /**
     * @param  list<string>  $defaultRoles
     */
    public function __construct(
        public string $ability,
        public string $label,
        public bool $requiresDirectoryContext,
        public array $defaultRoles,
        public bool $public = false,
    ) {}
}
