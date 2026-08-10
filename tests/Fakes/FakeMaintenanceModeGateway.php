<?php

namespace Tests\Fakes;

use App\Contracts\MaintenanceModeGateway;
use RuntimeException;

class FakeMaintenanceModeGateway implements MaintenanceModeGateway
{
    public bool $active = false;

    public bool $failActivation = false;

    public bool $failDeactivation = false;

    /** @var array<string, mixed> */
    public array $payload = [];

    public function active(): bool
    {
        return $this->active;
    }

    public function data(): array
    {
        return $this->payload;
    }

    public function activate(array $payload): void
    {
        if ($this->failActivation) {
            throw new RuntimeException('native activation secret detail');
        }

        $this->payload = $payload;
        $this->active = true;
    }

    public function deactivate(): void
    {
        if ($this->failDeactivation) {
            throw new RuntimeException('native deactivation secret detail');
        }

        $this->active = false;
        $this->payload = [];
    }
}
