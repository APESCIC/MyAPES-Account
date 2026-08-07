<?php

namespace App\Console\Commands;

use App\Exceptions\ModuleLifecycleException;
use App\Services\ModulePreflightChecker;
use Illuminate\Console\Command;
use Throwable;

class ModulesPreflight extends Command
{
    protected $signature = 'myapes:modules:preflight';

    protected $description = 'Validate the code-owned module registry before migration';

    public function handle(ModulePreflightChecker $checker): int
    {
        try {
            $result = $checker->check();
        } catch (ModuleLifecycleException $exception) {
            $this->components->error(
                "Module preflight: failed ({$exception->reason})",
            );

            return self::FAILURE;
        } catch (Throwable) {
            $this->components->error(
                'Module preflight: failed (verification_failed)',
            );

            return self::FAILURE;
        }

        $this->components->info(
            "Module registry: ok ({$result['sub_cores']} sub-cores, ".
            "{$result['modules']} module types)",
        );
        $this->components->info(
            "Shipped module code: ok ({$result['shipped']} instances)",
        );

        return self::SUCCESS;
    }
}
