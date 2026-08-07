<?php

namespace App\Services;

use App\Exceptions\ModuleLifecycleException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ModuleInstanceLock
{
    public function run(
        string $subCoreKey,
        string $moduleKey,
        callable $operation,
    ): mixed {
        return $this->runMany(
            ["{$subCoreKey}:{$moduleKey}"],
            $operation,
        );
    }

    /** @param array<int, string> $instanceKeys */
    public function runMany(array $instanceKeys, callable $operation): mixed
    {
        $instanceKeys = array_values(array_unique($instanceKeys));
        sort($instanceKeys);

        foreach ($instanceKeys as $instanceKey) {
            if (preg_match(
                '/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*:[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/D',
                $instanceKey,
            ) !== 1) {
                throw new ModuleLifecycleException('instance_lock_invalid');
            }
        }

        $lockedOperation = $operation;

        foreach (array_reverse($instanceKeys) as $instanceKey) {
            $next = $lockedOperation;
            $lockedOperation = fn (): mixed => $this->runOne(
                $instanceKey,
                $next,
            );
        }

        return $lockedOperation();
    }

    private function runOne(string $instanceKey, callable $operation): mixed
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'mysql', 'mariadb' => $this->runWithDatabaseLock(
                $instanceKey,
                $operation,
            ),
            'sqlite' => $this->runWithFileLock($instanceKey, $operation),
            default => throw new ModuleLifecycleException(
                'instance_lock_unsupported',
            ),
        };
    }

    private function runWithDatabaseLock(
        string $instanceKey,
        callable $operation,
    ): mixed {
        $connection = DB::connection();
        $databaseNamespace = trim((string) $connection->getDatabaseName());

        if ($databaseNamespace === '') {
            throw new ModuleLifecycleException('instance_lock_unavailable');
        }

        $lockName = 'myapes:module:'.substr(
            hash('sha256', $databaseNamespace."\0".$instanceKey),
            0,
            48,
        );
        $waitSeconds = max(
            0,
            (int) config('modules.lock_wait_seconds', 5),
        );
        $result = $connection->selectOne(
            'SELECT GET_LOCK(?, ?) AS acquired',
            [$lockName, $waitSeconds],
        );

        if ((int) ($result?->acquired ?? 0) !== 1) {
            throw new ModuleLifecycleException('instance_busy');
        }

        try {
            return $operation();
        } finally {
            $this->releaseDatabaseLock($connection, $lockName);
        }
    }

    private function releaseDatabaseLock(
        ConnectionInterface $connection,
        string $lockName,
    ): void {
        try {
            $released = $connection->selectOne(
                'SELECT RELEASE_LOCK(?) AS released',
                [$lockName],
            );

            if ((int) ($released?->released ?? 0) === 1) {
                return;
            }
        } catch (Throwable) {
            // Purging the connection also releases any remaining named lock.
        }

        DB::purge($connection->getName());
        Log::warning('Module instance lock release was not confirmed.', [
            'reason' => 'release_unconfirmed',
        ]);
    }

    private function runWithFileLock(
        string $instanceKey,
        callable $operation,
    ): mixed {
        $directory = storage_path('framework/cache/module-locks');

        if (! is_dir($directory)
            && ! @mkdir($directory, 0770, true)
            && ! is_dir($directory)) {
            throw new ModuleLifecycleException('instance_lock_unavailable');
        }

        $path = $directory.DIRECTORY_SEPARATOR
            .hash('sha256', $instanceKey).'.lock';
        $handle = @fopen($path, 'c+');

        if ($handle === false) {
            throw new ModuleLifecycleException('instance_lock_unavailable');
        }

        $waitSeconds = max(
            0,
            (int) config('modules.lock_wait_seconds', 5),
        );
        $deadline = microtime(true) + $waitSeconds;
        $acquired = false;

        do {
            $acquired = flock($handle, LOCK_EX | LOCK_NB);

            if (! $acquired && microtime(true) < $deadline) {
                usleep(25_000);
            }
        } while (! $acquired && microtime(true) < $deadline);

        if (! $acquired) {
            fclose($handle);

            throw new ModuleLifecycleException('instance_busy');
        }

        try {
            return $operation();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
