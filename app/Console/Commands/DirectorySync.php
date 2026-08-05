<?php

namespace App\Console\Commands;

use App\Exceptions\DirectorySyncInProgress;
use App\Exceptions\DirectoryUnavailable;
use App\Models\DirectorySyncRun;
use App\Services\DirectoryCatalogueSynchronizer;
use Illuminate\Console\Command;
use Throwable;

class DirectorySync extends Command
{
    protected $signature = 'myapes:directory-sync
        {--source=manual : Synchronization source (manual or scheduled)}';

    protected $description = 'Synchronize the sanitized LDAP group catalogue';

    public function handle(DirectoryCatalogueSynchronizer $synchronizer): int
    {
        $source = $this->option('source');

        if (! is_string($source)
            || ! in_array($source, [
                DirectorySyncRun::SOURCE_MANUAL,
                DirectorySyncRun::SOURCE_SCHEDULED,
            ], true)) {
            $this->components->error(
                'Directory catalogue: failed (invalid_source)',
            );

            return self::FAILURE;
        }

        try {
            $run = $synchronizer->synchronize($source);
        } catch (DirectorySyncInProgress) {
            $this->components->error(
                'Directory catalogue: failed (sync_in_progress)',
            );

            return self::FAILURE;
        } catch (DirectoryUnavailable) {
            $this->components->error(
                'Directory catalogue: failed (directory_unavailable)',
            );

            return self::FAILURE;
        } catch (Throwable) {
            $this->components->error(
                'Directory catalogue: failed (synchronization_failed)',
            );

            return self::FAILURE;
        }

        $this->components->info(
            "Directory catalogue: ok ({$run->groups_seen} seen, ".
            "{$run->groups_missing} missing)",
        );

        if ($run->groups_missing > 0) {
            $this->components->warn(
                "Directory groups: warning ({$run->groups_missing} missing)",
            );
        }

        return self::SUCCESS;
    }
}
