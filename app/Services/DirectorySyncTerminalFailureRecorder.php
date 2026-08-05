<?php

namespace App\Services;

use App\Models\DirectorySyncRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class DirectorySyncTerminalFailureRecorder
{
    public function record(
        string $source,
        string $queueJobUuid,
        int $queueAttempt,
    ): void {
        $this->assertInput($source, $queueJobUuid, $queueAttempt);

        DB::transaction(function () use (
            $source,
            $queueJobUuid,
            $queueAttempt,
        ): void {
            $state = DB::table('authorization_states')
                ->where('id', 1)
                ->lockForUpdate()
                ->first();
            if ($state === null) {
                throw new RuntimeException(
                    'Directory synchronization terminal state is unavailable.',
                );
            }
            $run = DB::table('directory_sync_runs')
                ->where('queue_job_uuid', $queueJobUuid)
                ->where('queue_attempt', $queueAttempt)
                ->lockForUpdate()
                ->first();

            if ($run !== null && $run->source !== $source) {
                throw new InvalidArgumentException(
                    'Directory synchronization queue correlation is inconsistent.',
                );
            }

            $leaseOwnerToken = $run?->lease_owner_token;

            if ($run !== null
                && in_array($run->status, [
                    DirectorySyncRun::STATUS_SUCCEEDED,
                    DirectorySyncRun::STATUS_FAILED,
                ], true)) {
                $this->clearMatchingLease($state, $leaseOwnerToken);

                return;
            }

            if ($run === null) {
                $timestamp = now();
                DB::table('directory_sync_runs')->insertOrIgnore([
                    'source' => $source,
                    'status' => DirectorySyncRun::STATUS_FAILED,
                    'queue_job_uuid' => $queueJobUuid,
                    'queue_attempt' => $queueAttempt,
                    'lease_owner_token' => null,
                    'started_at' => $timestamp,
                    'finished_at' => $timestamp,
                    'groups_seen' => 0,
                    'groups_missing' => 0,
                    'error_code' => 'job_failed',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
                $run = DB::table('directory_sync_runs')
                    ->where('queue_job_uuid', $queueJobUuid)
                    ->where('queue_attempt', $queueAttempt)
                    ->lockForUpdate()
                    ->first();
                if ($run === null) {
                    throw new RuntimeException(
                        'Directory synchronization terminal state is unavailable.',
                    );
                }
                if ($run->source !== $source) {
                    throw new InvalidArgumentException(
                        'Directory synchronization queue correlation is inconsistent.',
                    );
                }
            }

            if ($run->status === DirectorySyncRun::STATUS_FAILED) {
                $this->clearMatchingLease(
                    $state,
                    $run->lease_owner_token,
                );

                return;
            }

            $leaseOwnerToken = $run->lease_owner_token;
            DB::table('directory_sync_runs')
                ->where('id', $run->id)
                ->update([
                    'status' => DirectorySyncRun::STATUS_FAILED,
                    'finished_at' => now(),
                    'groups_seen' => 0,
                    'groups_missing' => 0,
                    'error_code' => 'job_failed',
                    'updated_at' => now(),
                ]);

            $this->clearMatchingLease($state, $leaseOwnerToken);
        });
    }

    private function clearMatchingLease(
        object $state,
        mixed $leaseOwnerToken,
    ): void {
        if (! is_string($leaseOwnerToken)
            || $leaseOwnerToken === ''
            || ! is_string($state->directory_sync_owner_token)
            || ! hash_equals(
                $leaseOwnerToken,
                $state->directory_sync_owner_token,
            )) {
            return;
        }

        DB::table('authorization_states')
            ->where('id', 1)
            ->where('directory_sync_owner_token', $leaseOwnerToken)
            ->update([
                'directory_sync_owner_token' => null,
                'directory_sync_expires_at' => null,
                'updated_at' => now(),
            ]);
    }

    private function assertInput(
        string $source,
        string $queueJobUuid,
        int $queueAttempt,
    ): void {
        if (! in_array($source, [
            DirectorySyncRun::SOURCE_MANUAL,
            DirectorySyncRun::SOURCE_SCHEDULED,
        ], true)) {
            throw new InvalidArgumentException(
                'Directory synchronization source is invalid.',
            );
        }

        if (! Str::isUuid($queueJobUuid)) {
            throw new InvalidArgumentException(
                'Directory synchronization queue correlation is invalid.',
            );
        }

        if ($queueAttempt < 1) {
            throw new InvalidArgumentException(
                'Directory synchronization queue attempt is invalid.',
            );
        }
    }
}
