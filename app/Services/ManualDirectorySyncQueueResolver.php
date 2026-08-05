<?php

namespace App\Services;

use App\Jobs\RunDirectorySync;
use DomainException;

class ManualDirectorySyncQueueResolver
{
    private const ASYNCHRONOUS_DRIVERS = [
        'database',
        'beanstalkd',
        'redis',
    ];

    public function resolve(): string
    {
        $connection = config('queue.default');
        $connections = config('queue.connections');

        if (! is_string($connection)
            || trim($connection) === ''
            || ! is_array($connections)) {
            $this->deny();
        }

        $this->assertAsynchronous(
            $connection,
            $connections,
            [],
        );

        return $connection;
    }

    /**
     * @param  array<string, mixed>  $connections
     * @param  array<string, true>  $path
     */
    private function assertAsynchronous(
        string $connection,
        array $connections,
        array $path,
    ): void {
        if (isset($path[$connection])) {
            $this->deny();
        }

        $configuration = $connections[$connection] ?? null;

        if (! is_array($configuration)) {
            $this->deny();
        }

        $driver = $configuration['driver'] ?? null;

        if (! is_string($driver) || trim($driver) === '') {
            $this->deny();
        }

        if (in_array($driver, self::ASYNCHRONOUS_DRIVERS, true)) {
            $retryAfter = $configuration['retry_after'] ?? null;

            if (! is_int($retryAfter)
                || $retryAfter <= RunDirectorySync::TIMEOUT_SECONDS) {
                $this->deny();
            }

            return;
        }

        if ($driver !== 'failover') {
            $this->deny();
        }

        $children = $configuration['connections'] ?? null;

        if (! is_array($children) || $children === []) {
            $this->deny();
        }

        $path[$connection] = true;

        foreach ($children as $child) {
            if (! is_string($child) || trim($child) === '') {
                $this->deny();
            }

            $this->assertAsynchronous($child, $connections, $path);
        }
    }

    private function deny(): never
    {
        throw new DomainException(
            'Manual directory synchronization requires a safely asynchronous queue.',
        );
    }
}
