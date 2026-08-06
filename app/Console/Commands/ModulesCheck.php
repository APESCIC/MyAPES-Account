<?php

namespace App\Console\Commands;

use App\Exceptions\ModuleLifecycleException;
use App\Services\ModuleIntegrityChecker;
use Illuminate\Console\Command;
use Throwable;

class ModulesCheck extends Command
{
    protected $signature = 'myapes:modules:check';

    protected $description = 'Read-only verification of module registry and installation integrity';

    public function handle(ModuleIntegrityChecker $checker): int
    {
        try {
            $result = $checker->check();
        } catch (ModuleLifecycleException $exception) {
            $this->components->error(
                "Module integrity: failed ({$exception->reason})",
            );

            return self::FAILURE;
        } catch (Throwable) {
            $this->components->error(
                'Module integrity: failed (verification_failed)',
            );

            return self::FAILURE;
        }

        $this->components->info(
            "Module integrity: ok ({$result['installations']} installations)",
        );
        $this->components->info(
            "Module permissions: ok ({$result['permissions']} permissions)",
        );

        return self::SUCCESS;
    }
}
