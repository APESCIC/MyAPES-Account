<?php

namespace App\Console\Commands;

use App\Exceptions\AuthReadinessException;
use App\Services\AuthReadinessChecker;
use Illuminate\Console\Command;

class MyapesAuthCheck extends Command
{
    protected $signature = 'myapes:auth-check';

    protected $description = 'Verify production OIDC and LDAP readiness without exposing credentials';

    public function handle(AuthReadinessChecker $checker): int
    {
        try {
            $groupCount = $checker->check();
        } catch (AuthReadinessException $exception) {
            $this->components->error(
                "Authentication readiness: failed ({$exception->check}/{$exception->reason})"
            );

            return self::FAILURE;
        }

        $this->components->info('OIDC configuration: ok');
        $this->components->info('OIDC discovery: ok');
        $this->components->info('LDAP bind: ok');
        $this->components->info("LDAP groups: ok ({$groupCount} required)");
        $this->components->info('Authentication readiness: ok');

        return self::SUCCESS;
    }
}
