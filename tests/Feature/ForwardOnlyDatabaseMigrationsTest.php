<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\Support\ForwardOnlyDatabaseMigrations;
use Tests\TestCase;

class ForwardOnlyDatabaseMigrationsTest extends TestCase
{
    use ForwardOnlyDatabaseMigrations;

    protected function afterRefreshingDatabase(): void
    {
        $this->fakeMaintenanceMode();
    }

    public function test_forward_only_child_tables_do_not_block_legacy_migration_rollbacks(): void
    {
        $this->assertTrue(Schema::hasTable('case_updates'));

        $this->assertSame(0, $this->rollBackDatabaseMigrations());
        $this->assertFalse(Schema::hasTable('case_updates'));
        $this->assertFalse(Schema::hasTable('shelter_cases'));
    }
}
