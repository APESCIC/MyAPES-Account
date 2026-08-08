<?php

namespace App\Console\Commands;

use App\Services\AccountLifecycleReadinessChecker;
use Illuminate\Console\Command;
use Throwable;

class AccountsPreflight extends Command
{
    protected $signature = 'myapes:accounts:preflight';

    protected $description = 'Validate account lifecycle prerequisites before migration';

    public function handle(AccountLifecycleReadinessChecker $checker): int
    {
        try {
            $result = $checker->preflight();
        } catch (Throwable $exception) {
            $this->components->error(
                "Account lifecycle preflight: failed ({$exception->getMessage()})",
            );

            return self::FAILURE;
        }

        $this->components->info(
            "Account lifecycle preflight: ok ({$result['users']} users)",
        );

        return self::SUCCESS;
    }
}
