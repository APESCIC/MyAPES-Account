<?php

namespace Tests\Unit;

use App\Services\LaravelMaintenanceModeGateway;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LaravelMaintenanceModeGatewayTest extends TestCase
{
    public function test_data_does_not_read_the_down_file_when_maintenance_is_inactive(): void
    {
        $mode = new class implements MaintenanceMode
        {
            public function activate(array $payload): void {}

            public function deactivate(): void {}

            public function active(): bool
            {
                return false;
            }

            public function data(): array
            {
                throw new RuntimeException('Inactive maintenance must not read the down file.');
            }
        };

        $gateway = new LaravelMaintenanceModeGateway($mode);

        $this->assertSame([], $gateway->data());
    }

    public function test_data_returns_the_native_payload_when_maintenance_is_active(): void
    {
        $mode = new class implements MaintenanceMode
        {
            public function activate(array $payload): void {}

            public function deactivate(): void {}

            public function active(): bool
            {
                return true;
            }

            public function data(): array
            {
                return ['status' => 503];
            }
        };

        $gateway = new LaravelMaintenanceModeGateway($mode);

        $this->assertSame(['status' => 503], $gateway->data());
    }
}
