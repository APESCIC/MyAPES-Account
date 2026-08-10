<?php

namespace App\Services;

use App\Contracts\MaintenanceModeGateway;
use Illuminate\Contracts\Foundation\MaintenanceMode;

class LaravelMaintenanceModeGateway implements MaintenanceModeGateway
{
    public function __construct(private readonly MaintenanceMode $maintenanceMode) {}

    public function active(): bool
    {
        return $this->maintenanceMode->active();
    }

    public function data(): array
    {
        return $this->maintenanceMode->data();
    }

    public function activate(array $payload): void
    {
        $this->maintenanceMode->activate($payload);
    }

    public function deactivate(): void
    {
        $this->maintenanceMode->deactivate();
    }
}
