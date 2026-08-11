<?php

namespace Tests\Support;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Schema;

trait ForwardOnlyDatabaseMigrations
{
    use DatabaseMigrations;

    public function runDatabaseMigrations(): void
    {
        $this->beforeRefreshingDatabase();
        $this->refreshTestDatabase();
        $this->afterRefreshingDatabase();

        $this->beforeApplicationDestroyed(function (): void {
            $this->rollBackDatabaseMigrations();

            RefreshDatabaseState::$migrated = false;
        });
    }

    protected function rollBackDatabaseMigrations(): int
    {
        Schema::dropIfExists('case_updates');

        return $this->artisan('migrate:rollback')->run();
    }
}
