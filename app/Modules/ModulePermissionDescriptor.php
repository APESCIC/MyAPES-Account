<?php

namespace App\Modules;

final readonly class ModulePermissionDescriptor
{
    /** @param array<int, string> $defaultRoles */
    public function __construct(
        public string $name,
        public string $subCoreKey,
        public string $moduleKey,
        public string $ability,
        public string $label,
        public bool $requiresDirectoryContext,
        public array $defaultRoles,
    ) {}
}
