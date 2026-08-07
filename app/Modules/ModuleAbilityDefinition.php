<?php

namespace App\Modules;

final readonly class ModuleAbilityDefinition
{
    /**
     * @param  array<int, string>  $defaultRoles
     */
    public function __construct(
        public string $ability,
        public string $label,
        public bool $requiresDirectoryContext,
        public array $defaultRoles,
    ) {}
}
