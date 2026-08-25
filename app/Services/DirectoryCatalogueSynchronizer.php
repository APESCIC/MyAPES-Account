<?php

namespace App\Services;

use App\Exceptions\DirectorySyncInProgress;
use App\Exceptions\DirectoryUnavailable;
use App\Models\AuthorizationState;
use App\Models\DirectoryGroup;
use App\Models\DirectorySyncRun;
use App\Support\DirectoryGroupPrefix;
use App\Support\DirectoryLegacyGroupAliases;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class DirectoryCatalogueSynchronizer
{
    public function __construct(
        private readonly LdapGroupResolver $directory,
        private readonly DirectoryUserSynchronizer $users,
    ) {}

    public function synchronize(
        string $source,
        ?string $queueJobUuid = null,
        ?int $queueAttempt = null,
    ): DirectorySyncRun {
        $this->assertSource($source);
        $this->assertQueueCorrelation($queueJobUuid, $queueAttempt);
        $ownerToken = $this->acquireLease();

        try {
            return $this->synchronizeWhileLeased(
                $source,
                $ownerToken,
                $queueJobUuid,
                $queueAttempt,
            );
        } finally {
            $this->releaseLease($ownerToken);
        }
    }

    private function synchronizeWhileLeased(
        string $source,
        string $ownerToken,
        ?string $queueJobUuid,
        ?int $queueAttempt,
    ): DirectorySyncRun {
        $run = DirectorySyncRun::query()->create([
            'source' => $source,
            'status' => DirectorySyncRun::STATUS_RUNNING,
            'queue_job_uuid' => $queueJobUuid,
            'queue_attempt' => $queueAttempt,
            'lease_owner_token' => $ownerToken,
            'started_at' => now(),
            'finished_at' => null,
            'groups_seen' => 0,
            'groups_missing' => 0,
            'error_code' => null,
        ]);

        try {
            $groups = $this->directory->enumerateGroups();
        } catch (Throwable) {
            $this->markFailed($run, 'directory_unavailable');

            throw new DirectoryUnavailable(
                'Directory catalogue synchronization is unavailable.',
            );
        }

        try {
            $groups = $this->managedCatalogueOnly(
                $this->validatedCatalogue($groups),
            );
        } catch (Throwable) {
            $this->markFailed($run, 'invalid_catalogue');

            throw new DirectoryUnavailable(
                'Directory catalogue synchronization is unavailable.',
            );
        }

        try {
            return DB::transaction(function () use (
                $run,
                $groups,
                $ownerToken,
            ): DirectorySyncRun {
                $this->assertCurrentLease($ownerToken);
                $synchronizedAt = now();
                $names = [];

                foreach ($groups as $group) {
                    $names[] = $group['name'];
                    $stored = DirectoryGroup::query()
                        ->where('name', $group['name'])
                        ->lockForUpdate()
                        ->first();

                    if ($stored === null) {
                        $stored = new DirectoryGroup;
                        $stored->name = $group['name'];
                        $stored->first_seen_at = $synchronizedAt;
                        if (Schema::hasColumn('directory_groups', 'app_enabled')) {
                            $stored->app_enabled = DirectoryGroup::defaultAppEnabledForName(
                                $group['name'],
                            );
                        }
                    } elseif ($stored->first_seen_at === null) {
                        $stored->first_seen_at = $synchronizedAt;
                    }

                    $payload = [
                        'external_id' => $group['external_id'],
                        'member_count' => $group['member_count'],
                        'status' => DirectoryGroup::STATUS_PRESENT,
                        'last_seen_at' => $synchronizedAt,
                        'last_synced_at' => $synchronizedAt,
                    ];

                    if (
                        Schema::hasColumn('directory_groups', 'app_enabled')
                        && DirectoryGroup::isAlwaysEnabledName($group['name'])
                    ) {
                        $payload['app_enabled'] = true;
                    }

                    $stored->forceFill($payload)->save();
                }

                $missing = DirectoryGroup::query()
                    ->whereIn('name', DirectoryGroupPrefix::requiredGroups());

                if ($names !== []) {
                    $missing->whereNotIn('name', $names);
                }

                $missing->update([
                    'status' => DirectoryGroup::STATUS_MISSING,
                    'last_synced_at' => $synchronizedAt,
                    'updated_at' => $synchronizedAt,
                ]);
                $missingCount = DirectoryGroup::query()
                    ->where('status', DirectoryGroup::STATUS_MISSING)
                    ->count();

                $run->forceFill([
                    'status' => DirectorySyncRun::STATUS_SUCCEEDED,
                    'finished_at' => $synchronizedAt,
                    'groups_seen' => count($groups),
                    'groups_missing' => $missingCount,
                    'error_code' => null,
                ])->save();
                $this->assertCurrentLease($ownerToken);

                $userStats = $this->users->synchronize();

                if (Schema::hasColumns('directory_sync_runs', [
                    'users_seen',
                    'users_created',
                    'users_updated',
                ])) {
                    $run->forceFill([
                        'users_seen' => $userStats['seen'],
                        'users_created' => $userStats['created'],
                        'users_updated' => $userStats['updated'],
                    ])->save();
                }

                return $run->refresh();
            });
        } catch (Throwable) {
            $this->markFailed($run, 'catalogue_persistence_failed');

            throw new DirectoryUnavailable(
                'Directory catalogue synchronization is unavailable.',
            );
        }
    }

    private function acquireLease(): string
    {
        $ownerToken = Str::random(64);

        try {
            DB::transaction(function () use ($ownerToken): void {
                $state = AuthorizationState::query()
                    ->whereKey(AuthorizationState::SINGLETON_ID)
                    ->lockForUpdate()
                    ->first();

                if ($state === null) {
                    throw new DirectoryUnavailable(
                        'Directory synchronization lease is unavailable.',
                    );
                }

                $currentOwner = $state->directory_sync_owner_token;
                $currentExpiry = $state->directory_sync_expires_at;

                if (is_string($currentOwner)
                    && $currentOwner !== ''
                    && $currentExpiry !== null
                    && $currentExpiry->isFuture()) {
                    throw new DirectorySyncInProgress(
                        'Directory catalogue synchronization is already running.',
                    );
                }

                $state->forceFill([
                    'directory_sync_owner_token' => $ownerToken,
                    'directory_sync_expires_at' => now()
                        ->addSeconds($this->leaseSeconds()),
                ])->save();
            });
        } catch (DirectorySyncInProgress $exception) {
            throw $exception;
        } catch (Throwable) {
            throw new DirectoryUnavailable(
                'Directory synchronization lease is unavailable.',
            );
        }

        return $ownerToken;
    }

    private function assertCurrentLease(string $ownerToken): void
    {
        $state = AuthorizationState::query()
            ->whereKey(AuthorizationState::SINGLETON_ID)
            ->lockForUpdate()
            ->first();
        $currentOwner = $state?->directory_sync_owner_token;
        $currentExpiry = $state?->directory_sync_expires_at;

        if (! is_string($currentOwner)
            || ! hash_equals($ownerToken, $currentOwner)
            || $currentExpiry === null
            || ! $currentExpiry->isFuture()) {
            throw new DirectoryUnavailable(
                'Directory synchronization lease is unavailable.',
            );
        }
    }

    private function releaseLease(string $ownerToken): void
    {
        try {
            DB::transaction(function () use ($ownerToken): void {
                $state = AuthorizationState::query()
                    ->whereKey(AuthorizationState::SINGLETON_ID)
                    ->lockForUpdate()
                    ->first();
                $currentOwner = $state?->directory_sync_owner_token;

                if (! is_string($currentOwner)
                    || ! hash_equals($ownerToken, $currentOwner)) {
                    return;
                }

                $state->forceFill([
                    'directory_sync_owner_token' => null,
                    'directory_sync_expires_at' => null,
                ])->save();
            });
        } catch (Throwable) {
            // A crashed release remains recoverable after the bounded expiry.
        }
    }

    private function leaseSeconds(): int
    {
        return max(
            30,
            min(
                900,
                (int) config('myapes.directory.sync_lock_seconds', 300),
            ),
        );
    }

    private function assertSource(string $source): void
    {
        if (! in_array($source, [
            DirectorySyncRun::SOURCE_MANUAL,
            DirectorySyncRun::SOURCE_SCHEDULED,
        ], true)) {
            throw new InvalidArgumentException(
                'Directory synchronization source is invalid.',
            );
        }
    }

    private function assertQueueCorrelation(
        ?string $queueJobUuid,
        ?int $queueAttempt,
    ): void {
        if ($queueJobUuid === null && $queueAttempt === null) {
            return;
        }

        if (! is_string($queueJobUuid)
            || ! Str::isUuid($queueJobUuid)
            || ! is_int($queueAttempt)
            || $queueAttempt < 1) {
            throw new InvalidArgumentException(
                'Directory synchronization queue correlation is invalid.',
            );
        }
    }

    /**
     * @param  array<int, mixed>  $groups
     * @return array<int, array{name: string, external_id: ?string, member_count: int}>
     */
    private function validatedCatalogue(array $groups): array
    {
        $validated = [];
        $names = [];
        $externalIds = [];

        foreach ($groups as $group) {
            if (! is_array($group)
                || array_diff(
                    array_keys($group),
                    ['name', 'external_id', 'member_count'],
                ) !== []
                || array_diff(
                    ['name', 'external_id', 'member_count'],
                    array_keys($group),
                ) !== []) {
                throw new InvalidArgumentException('Invalid directory group shape.');
            }

            $name = $group['name'];
            $externalId = $group['external_id'];
            $memberCount = $group['member_count'];

            if (! is_string($name)
                || trim($name) === ''
                || $name !== strtolower(trim($name))
                || mb_strlen($name) > 191
                || preg_match('/[\x00-\x1F\x7F]/', $name) === 1
                || isset($names[$name])
                || (! is_null($externalId)
                    && (! is_string($externalId)
                        || trim($externalId) === ''
                        || mb_strlen($externalId) > 191
                        || isset($externalIds[$externalId])))
                || ! is_int($memberCount)
                || $memberCount < 0) {
                throw new InvalidArgumentException('Invalid directory group metadata.');
            }

            $names[$name] = true;

            if ($externalId !== null) {
                $externalIds[$externalId] = true;
            }

            $validated[] = [
                'name' => $name,
                'external_id' => $externalId,
                'member_count' => $memberCount,
            ];
        }

        return $validated;
    }

    /**
     * Keep only managed MyAPES Account Cloudron groups (canonical myapesaccount.*).
     * Legacy/typo aliases are rewritten; unrelated directory noise is dropped.
     *
     * @param  array<int, array{name: string, external_id: ?string, member_count: int}>  $groups
     * @return array<int, array{name: string, external_id: ?string, member_count: int}>
     */
    private function managedCatalogueOnly(array $groups): array
    {
        $managed = [];

        foreach ($groups as $group) {
            $canonical = DirectoryLegacyGroupAliases::canonicalFor($group['name']);

            if ($canonical === null) {
                continue;
            }

            if (isset($managed[$canonical])) {
                $existing = $managed[$canonical];
                $managed[$canonical] = [
                    'name' => $canonical,
                    'external_id' => $existing['external_id'] ?? $group['external_id'],
                    'member_count' => max(
                        $existing['member_count'],
                        $group['member_count'],
                    ),
                ];

                continue;
            }

            $managed[$canonical] = [
                'name' => $canonical,
                'external_id' => $group['external_id'],
                'member_count' => $group['member_count'],
            ];
        }

        $managed = array_values($managed);
        usort(
            $managed,
            static fn (array $left, array $right): int => strcmp(
                $left['name'],
                $right['name'],
            ),
        );

        return $managed;
    }

    private function markFailed(DirectorySyncRun $run, string $code): void
    {
        $run->forceFill([
            'status' => DirectorySyncRun::STATUS_FAILED,
            'finished_at' => now(),
            'groups_seen' => 0,
            'groups_missing' => 0,
            'error_code' => $code,
        ])->save();
    }
}
