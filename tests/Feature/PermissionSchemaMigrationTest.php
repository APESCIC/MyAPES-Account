<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class PermissionSchemaMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_package_schema_installation_is_cleaned_up_and_retryable(): void
    {
        $this->runInMaintenanceMode(
            fn () => $this->authorizationMigration()->down(),
        );
        $migration = $this->permissionMigration();
        $migration->down();

        $originalStore = config('permission.cache.store');
        config(['permission.cache.store' => 'missing-permission-cache-store']);

        try {
            $migration->up();
            $this->fail('The migration accepted an unavailable permission cache store.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString(
                'missing-permission-cache-store',
                $exception->getMessage(),
            );
        } finally {
            config(['permission.cache.store' => $originalStore]);
        }

        foreach ($this->permissionTables() as $table) {
            $this->assertFalse(
                Schema::hasTable($table),
                "Expected partial permission table [{$table}] to be removed.",
            );
        }

        $migration->up();

        foreach ($this->permissionTables() as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Expected permission table [{$table}] after retry.",
            );
        }
    }

    private function authorizationMigration(): Migration
    {
        /** @var Migration $migration */
        $migration = require database_path(
            'migrations/2026_07_28_000100_cut_over_authorization_domain.php',
        );

        return $migration;
    }

    private function permissionMigration(): Migration
    {
        /** @var Migration $migration */
        $migration = require database_path(
            'migrations/2026_07_28_000000_create_permission_tables.php',
        );

        return $migration;
    }

    /**
     * @return array<int, string>
     */
    private function permissionTables(): array
    {
        return [
            'role_has_permissions',
            'model_has_roles',
            'model_has_permissions',
            'roles',
            'permissions',
        ];
    }
}
