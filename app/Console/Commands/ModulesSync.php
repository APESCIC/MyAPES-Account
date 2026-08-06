<?php

namespace App\Console\Commands;

use App\Exceptions\ModuleLifecycleException;
use App\Services\ModuleInstallationSynchronizer;
use Illuminate\Console\Command;
use Throwable;

class ModulesSync extends Command
{
    protected $signature = 'myapes:modules:sync';

    protected $description = 'Create missing first-party module installation defaults';

    public function handle(ModuleInstallationSynchronizer $synchronizer): int
    {
        try {
            $result = $synchronizer->synchronize();
        } catch (ModuleLifecycleException $exception) {
            $this->components->error(
                "Module synchronization: failed ({$exception->reason})",
            );

            return self::FAILURE;
        } catch (Throwable) {
            $this->components->error(
                'Module synchronization: failed (synchronization_failed)',
            );

            return self::FAILURE;
        }

        $this->components->info(
            "Module synchronization: ok ({$result['created']} created, ".
            "{$result['existing']} existing)",
        );

        return self::SUCCESS;
    }
}
