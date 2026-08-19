<?php

namespace Tests\Unit;

use App\Services\LaravelMaintenanceModeGateway;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use RuntimeException;
use Tests\TestCase;

class LaravelMaintenanceModeGatewayTest extends TestCase
{
    public function test_data_does_not_read_the_down_file_when_the_application_is_up(): void
    {
        $native = new class implements MaintenanceMode
        {
            public int $dataCalls = 0;

            public function activate(array $payload): void {}

            public function deactivate(): void {}

            public function active(): bool
            {
                return false;
            }

            public function data(): array
            {
                $this->dataCalls++;
                throw new RuntimeException('down file missing');
            }
        };

        $gateway = new LaravelMaintenanceModeGateway($native);

        $this->assertSame([], $gateway->data());
        $this->assertSame(0, $native->dataCalls);
    }

    public function test_data_reads_native_payload_when_maintenance_is_active(): void
    {
        $native = new class implements MaintenanceMode
        {
            public function activate(array $payload): void {}

            public function deactivate(): void {}

            public function active(): bool
            {
                return true;
            }

            public function data(): array
            {
                return ['retry' => 60];
            }
        };

        $gateway = new LaravelMaintenanceModeGateway($native);

        $this->assertSame(['retry' => 60], $gateway->data());
    }
}
