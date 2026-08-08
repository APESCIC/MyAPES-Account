<?php

namespace App\Console\Commands;

use App\Services\AccountLifecycleReadinessChecker;
use Illuminate\Console\Command;
use Throwable;

class AccountsCheck extends Command
{
    protected $signature = 'myapes:accounts:check';

    protected $description = 'Read-only verification of account lifecycle readiness';

    public function handle(AccountLifecycleReadinessChecker $checker): int
    {
        try {
            $result = $checker->check();
        } catch (Throwable $exception) {
            $this->components->error(
                "Account lifecycle readiness: failed ({$exception->getMessage()})",
            );

            return self::FAILURE;
        }

        $this->components->info(
            "Account lifecycle readiness: ok ({$result['users']} users)",
        );

        return self::SUCCESS;
    }
}
