<?php

use App\Models\AuthorizationState;
use App\Models\DirectorySyncRun;
use Illuminate\Contracts\Console\Kernel;
use Tests\Support\DirectorySyncTimeoutProbeJob;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$action = $argv[1] ?? '';

if ($action === 'prepare') {
    if (! function_exists('pcntl_alarm')) {
        fwrite(STDERR, "PCNTL is required for the timeout probe.\n");
        exit(1);
    }

    DirectorySyncTimeoutProbeJob::dispatch()
        ->onConnection('database')
        ->onQueue('security-timeout');
    fwrite(STDOUT, "Directory timeout probe queued.\n");
    exit(0);
}

if ($action === 'assert') {
    $runs = DirectorySyncRun::query()
        ->whereNotNull('queue_job_uuid')
        ->where('error_code', 'job_failed')
        ->get();
    $state = AuthorizationState::query()
        ->whereKey(AuthorizationState::SINGLETON_ID)
        ->first();
    $valid = $runs->count() === 1
        && $runs->first()?->status === DirectorySyncRun::STATUS_FAILED
        && $runs->first()?->queue_attempt === 1
        && $runs->first()?->finished_at !== null
        && $state?->directory_sync_owner_token === null
        && $state?->directory_sync_expires_at === null;

    if (! $valid) {
        fwrite(STDERR, "Directory timeout probe did not reach a safe terminal state.\n");
        exit(1);
    }

    fwrite(STDOUT, "Directory timeout probe reached a safe terminal state.\n");
    exit(0);
}

fwrite(STDERR, "Use prepare or assert.\n");
exit(1);
