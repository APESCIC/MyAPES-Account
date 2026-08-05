<?php

namespace App\Console\Commands;

use App\Exceptions\AuthorizationLifecycleException;
use App\Services\AuthorizationIntegrityChecker;
use Illuminate\Console\Command;
use Throwable;

class AuthorizationCheck extends Command
{
    protected $signature = 'myapes:authorization-check';

    protected $description = 'Read-only verification of authorization integrity';

    public function handle(AuthorizationIntegrityChecker $checker): int
    {
        try {
            $result = $checker->check();
        } catch (AuthorizationLifecycleException $exception) {
            $this->components->error(
                "Authorization check: failed ({$exception->check})",
            );

            return self::FAILURE;
        } catch (Throwable) {
            $this->components->error(
                'Authorization check: failed (verification_failed)',
            );

            return self::FAILURE;
        }

        $this->components->info('Authorization schema: ok');
        $this->components->info(
            "Permission matrix: ok ({$result['permissions']} permissions)",
        );
        $this->components->info(
            "Directory mappings: ok ({$result['immutable_mappings']} immutable)",
        );
        $this->components->info(
            "Role provenance: ok ({$result['users']} users)",
        );
        $this->components->info('Session cutover: ok');
        $this->components->info(
            "Effective super-admins: ok ({$result['super_admins']} users)",
        );
        $this->components->info(
            "Authorization check: ok ({$result['users']} users)",
        );

        return self::SUCCESS;
    }
}
