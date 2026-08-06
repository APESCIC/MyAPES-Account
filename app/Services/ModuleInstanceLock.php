<?php

namespace App\Services;

use App\Exceptions\ModuleLifecycleException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

class ModuleInstanceLock
{
    public function run(
        string $subCoreKey,
        string $moduleKey,
        callable $operation,
    ): mixed {
        $key = hash('sha256', "{$subCoreKey}:{$moduleKey}");
        $lockSeconds = max(1, (int) config('modules.lock_seconds', 15));
        $waitSeconds = max(
            0,
            min(
                $lockSeconds - 1,
                (int) config('modules.lock_wait_seconds', 5),
            ),
        );

        try {
            return Cache::lock(
                "myapes:module-instance:{$key}",
                $lockSeconds,
            )->block($waitSeconds, $operation);
        } catch (LockTimeoutException) {
            throw new ModuleLifecycleException('instance_busy');
        }
    }
}
