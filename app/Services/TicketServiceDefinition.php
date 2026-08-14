<?php

namespace App\Services;

final readonly class TicketServiceDefinition
{
    /** @param array<int, string> $serviceAreas */
    public function __construct(
        public string $serviceName,
        public string $routePrefix,
        public string $auditPrefix,
        public string $presentationClass,
        public string $heading,
        public string $supportingCopy,
        public array $serviceAreas,
        public bool $supportsDelete,
    ) {}
}
