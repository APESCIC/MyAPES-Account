<?php

namespace App\Console\Commands;

use App\Exceptions\ModuleLifecycleException;
use App\Services\ModuleRollbackCompatibilityChecker;
use Illuminate\Console\Command;
use Throwable;

class ModulesRollbackCheck extends Command
{
    protected $signature = 'myapes:modules:rollback-check
        {--target-release= : Absolute path to the release being restored}';

    protected $description = 'Verify module state is representable by a rollback target';

    public function handle(ModuleRollbackCompatibilityChecker $checker): int
    {
        $targetRelease = $this->option('target-release');

        if (! is_string($targetRelease) || trim($targetRelease) === '') {
            $this->components->error(
                'Module rollback compatibility: failed (target_release_required)',
            );

            return self::FAILURE;
        }

        try {
            $result = $checker->check($targetRelease);
        } catch (ModuleLifecycleException $exception) {
            $this->components->error(
                "Module rollback compatibility: failed ({$exception->reason})",
            );

            return self::FAILURE;
        } catch (Throwable) {
            $this->components->error(
                'Module rollback compatibility: failed (verification_failed)',
            );

            return self::FAILURE;
        }

        $this->components->info(
            "Module rollback compatibility: ok ({$result['contract']}, {$result['installations']} installations)",
        );

        return self::SUCCESS;
    }
}
