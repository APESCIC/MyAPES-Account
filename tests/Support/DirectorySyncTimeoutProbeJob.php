<?php

namespace Tests\Support;

use App\Models\AuthorizationState;
use App\Models\DirectorySyncRun;
use App\Services\DirectorySyncTerminalFailureRecorder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class DirectorySyncTimeoutProbeJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1;

    public bool $failOnTimeout = true;

    public function handle(): void
    {
        $uuid = $this->queueUuid();
        $ownerToken = hash('sha256', $uuid);

        DB::transaction(function () use ($uuid, $ownerToken): void {
            $state = AuthorizationState::query()
                ->whereKey(AuthorizationState::SINGLETON_ID)
                ->lockForUpdate()
                ->firstOrFail();
            $state->forceFill([
                'directory_sync_owner_token' => $ownerToken,
                'directory_sync_expires_at' => now()->addMinutes(5),
            ])->save();
            DirectorySyncRun::query()->create([
                'source' => DirectorySyncRun::SOURCE_SCHEDULED,
                'status' => DirectorySyncRun::STATUS_RUNNING,
                'queue_job_uuid' => $uuid,
                'queue_attempt' => $this->attempts(),
                'lease_owner_token' => $ownerToken,
                'started_at' => now(),
                'groups_seen' => 0,
                'groups_missing' => 0,
            ]);
        });

        sleep(5);
    }

    public function failed(?Throwable $exception): void
    {
        app(DirectorySyncTerminalFailureRecorder::class)->record(
            DirectorySyncRun::SOURCE_SCHEDULED,
            $this->queueUuid(),
            $this->attempts(),
        );
    }

    private function queueUuid(): string
    {
        $uuid = $this->job?->uuid();
        if (! is_string($uuid) || $uuid === '') {
            throw new RuntimeException(
                'The timeout probe requires a real queued job UUID.',
            );
        }

        return $uuid;
    }
}
