<?php

namespace App\Core\Extensions\Modules;

/**
 * Staff hub entry for a module (#283).
 */
final readonly class ModuleStaffArea
{
    public function __construct(
        public string $hubRouteName,
        public ?string $permission = null,
    ) {}
}
