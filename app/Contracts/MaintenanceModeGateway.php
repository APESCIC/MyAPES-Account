<?php

namespace App\Contracts;

interface MaintenanceModeGateway
{
    public function active(): bool;

    /** @return array<string, mixed> */
    public function data(): array;

    /** @param array<string, mixed> $payload */
    public function activate(array $payload): void;

    public function deactivate(): void;
}
