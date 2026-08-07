<?php

namespace Tests\Unit;

use App\Exceptions\ModuleLifecycleException;
use App\Services\ModuleInstanceLock;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class ModuleInstanceLockTest extends TestCase
{
    public function test_mysql_lock_names_are_scoped_to_the_active_database(): void
    {
        $first = $this->captureLockName('myapes_primary');
        $second = $this->captureLockName('myapes_sibling');

        $this->assertNotSame($first, $second);
        $this->assertStringStartsWith('myapes:module:', $first);
        $this->assertLessThanOrEqual(64, strlen($first));
    }

    public function test_mysql_lock_names_are_stable_for_the_same_database(): void
    {
        $first = $this->captureLockName('myapes_shared');
        $second = $this->captureLockName('myapes_shared');

        $this->assertSame($first, $second);
    }

    public function test_mysql_lock_creation_fails_closed_without_a_database_namespace(): void
    {
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');
        $connection->shouldReceive('getDatabaseName')->once()->andReturn('  ');
        $connection->shouldNotReceive('selectOne');

        $manager = Mockery::mock(DatabaseManager::class);
        $manager->shouldReceive('connection')->andReturn($connection);
        $original = DB::getFacadeRoot();
        DB::swap($manager);

        try {
            app(ModuleInstanceLock::class)->run(
                'apes-cic',
                'tickets',
                static fn (): null => null,
            );
            $this->fail('The lock was acquired without a database namespace.');
        } catch (ModuleLifecycleException $exception) {
            $this->assertSame('instance_lock_unavailable', $exception->reason);
        } finally {
            DB::swap($original);
        }
    }

    private function captureLockName(string $database): string
    {
        $captured = null;
        $connection = Mockery::mock(Connection::class);
        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');
        $connection->shouldReceive('getDatabaseName')->once()->andReturn($database);
        $connection->shouldReceive('selectOne')
            ->twice()
            ->andReturnUsing(static function (
                string $query,
                array $bindings,
            ) use (&$captured): object {
                if (str_contains($query, 'GET_LOCK')) {
                    $captured = $bindings[0] ?? null;

                    return (object) ['acquired' => 1];
                }

                return (object) ['released' => 1];
            });

        $manager = Mockery::mock(DatabaseManager::class);
        $manager->shouldReceive('connection')->andReturn($connection);
        $original = DB::getFacadeRoot();
        DB::swap($manager);

        try {
            app(ModuleInstanceLock::class)->run(
                'apes-cic',
                'tickets',
                static fn (): null => null,
            );
        } finally {
            DB::swap($original);
        }

        $this->assertIsString($captured);

        return $captured;
    }
}
