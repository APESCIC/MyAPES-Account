<?php

namespace App\Console\Commands;

use App\Exceptions\AuthorizationLifecycleException;
use App\Services\AuthorizationActivationSynchronizer;
use Illuminate\Console\Command;
use Throwable;

class AuthorizationSync extends Command
{
    protected $signature = 'myapes:authorization-sync';

    protected $description = 'Idempotently activate and synchronize authorization state';

    public function handle(AuthorizationActivationSynchronizer $synchronizer): int
    {
        try {
            $result = $synchronizer->synchronize();
        } catch (AuthorizationLifecycleException $exception) {
            $this->components->error(
                "Authorization sync: failed ({$exception->check})",
            );

            return self::FAILURE;
        } catch (Throwable) {
            $this->components->error(
                'Authorization sync: failed (synchronization_failed)',
            );

            return self::FAILURE;
        }

        $this->components->info(
            "Authorization sync: ok ({$result['users']} users, ".
            "{$result['directory_identities']} directory identities)",
        );
        $this->components->info(
            "Session cutover: complete ({$result['sessions_rotated']} users)",
        );

        return self::SUCCESS;
    }
}
