<?php

namespace Tests;

use App\Models\User;
use App\Services\SessionAuthorizationContext;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function actingAs(UserContract $user, $guard = null): static
    {
        parent::actingAs($user, $guard);

        if ($user instanceof User) {
            $values = app(SessionAuthorizationContext::class)->valuesFor(
                $user,
                SessionAuthorizationContext::METHOD_QA,
            );
            $this->withSession($values);

            if (! request()->hasSession()) {
                request()->setLaravelSession(app('session')->driver());
            }

            request()->session()->put($values);
        }

        return $this;
    }

    protected function fakeMaintenanceMode(bool $active = true): MaintenanceMode
    {
        $maintenanceMode = new class($active) implements MaintenanceMode
        {
            public function __construct(private bool $active) {}

            public function activate(array $payload): void
            {
                $this->active = true;
            }

            public function deactivate(): void
            {
                $this->active = false;
            }

            public function active(): bool
            {
                return $this->active;
            }

            public function data(): array
            {
                return [];
            }
        };

        $this->app->instance(MaintenanceMode::class, $maintenanceMode);

        return $maintenanceMode;
    }

    protected function runInMaintenanceMode(callable $operation): mixed
    {
        $originalMaintenanceMode = $this->app->make(MaintenanceMode::class);
        $this->fakeMaintenanceMode();

        try {
            return $operation();
        } finally {
            $this->app->instance(
                MaintenanceMode::class,
                $originalMaintenanceMode,
            );
        }
    }
}
