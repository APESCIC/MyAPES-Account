<?php

namespace App\Modules;

final readonly class SubCoreDefinition
{
    public function __construct(
        public string $key,
        public string $name,
        public string $description,
        public string $basePath,
        public string $routeName,
        public string $icon,
        public int $navigationOrder,
    ) {}
}
