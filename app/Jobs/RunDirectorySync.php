<?php

namespace App\Jobs;

use App\Models\DirectorySyncRun;
use App\Services\DirectoryCatalogueSynchronizer;
use App\Services\DirectorySyncTerminalFailureRecorder;
use App\Services\ManualDirectorySyncQueueResolver;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunDirectorySync implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public const TIMEOUT_SECONDS = 240;

    public int $tries = 3;

    public int $timeout = self::TIMEOUT_SECONDS;

    public bool $failOnTimeout = true;

    public function __construct(
        public string $source = DirectorySyncRun::SOURCE_MANUAL,
    ) {}

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function uniqueId(): string
    {
        return 'directory-catalogue-sync';
    }

    public function uniqueFor(): int
    {
        $maximumAttemptEnvelope = ($this->tries * $this->timeout)
            + array_sum($this->backoff());

        return $maximumAttemptEnvelope + (2 * $this->timeout);
    }

    public function handle(
        DirectoryCatalogueSynchronizer $synchronizer,
        ManualDirectorySyncQueueResolver $queueResolver,
    ): void {
        $queueResolver->resolve();
        $queueUuid = $this->queueUuid();
        $synchronizer->synchronize(
            $this->source,
            $queueUuid,
            $queueUuid === null ? null : $this->attempts(),
        );
    }

    public function failed(?Throwable $exception): void
    {
        try {
            app(DirectorySyncTerminalFailureRecorder::class)->record(
                $this->source,
                $this->queueUuid(),
                $this->attempts(),
            );
        } catch (Throwable $failure) {
            Log::critical('Directory synchronization terminal state failed.', [
                'source' => $this->source,
                'reason_code' => 'terminal_state_unavailable',
            ]);

            throw $failure;
        }

        Log::warning('Directory synchronization job failed.', [
            'source' => $this->source,
            'reason_code' => 'job_failed',
        ]);
    }

    private function queueUuid(): ?string
    {
        $uuid = $this->job?->uuid();

        return is_string($uuid) && $uuid !== '' ? $uuid : null;
    }
}
