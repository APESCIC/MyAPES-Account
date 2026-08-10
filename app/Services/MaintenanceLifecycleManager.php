<?php

namespace App\Services;

use App\Contracts\MaintenanceModeGateway;
use App\Exceptions\MaintenanceTransitionException;
use App\Models\MaintenanceWindow;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

class MaintenanceLifecycleManager
{
    public function __construct(
        private readonly MaintenanceModeGateway $maintenanceMode,
        private readonly AuditLogger $audit,
    ) {}

    /** @return array{active: bool, current: MaintenanceWindow|null, history: Collection<int, MaintenanceWindow>, problem: string|null} */
    public function status(): array
    {
        try {
            return $this->withLock(function (): array {
                $active = $this->maintenanceMode->active();
                $currentRows = MaintenanceWindow::query()
                    ->whereNotNull('active_guard')
                    ->get();

                if ($currentRows->count() > 1) {
                    $this->audit->record(
                        'maintenance.reconciliation_refused',
                        auth()->user(),
                        context: [
                            'action' => 'reconcile',
                            'reason_code' => 'duplicate_current_history',
                        ],
                    );

                    return $this->statusSnapshot(
                        $active,
                        null,
                        'Maintenance history contains conflicting current records. Transitions are disabled until an operator reviews the database.',
                    );
                }

                $current = $currentRows->first();

                if ($active && ! $current) {
                    $current = MaintenanceWindow::query()->create([
                        'message' => 'Maintenance activated outside the Admin console.',
                        'state' => MaintenanceWindow::STATE_ACTIVE,
                        'active_guard' => MaintenanceWindow::ACTIVE_GUARD,
                        'activated_at' => now(),
                    ]);
                    $this->recordReconciliation($current, 'native_active_without_history');
                }

                if ($current && $active && in_array($current->state, [
                    MaintenanceWindow::STATE_PENDING,
                    MaintenanceWindow::STATE_DEACTIVATION_PENDING,
                ], true)) {
                    $current->forceFill([
                        'state' => MaintenanceWindow::STATE_ACTIVE,
                        'activated_at' => $current->activated_at ?? now(),
                    ])->save();
                    $this->recordReconciliation($current, 'native_active');
                }

                if ($current
                    && ! $active
                    && $current->state === MaintenanceWindow::STATE_PENDING) {
                    $current->forceFill([
                        'state' => MaintenanceWindow::STATE_ACTIVATION_FAILED,
                        'active_guard' => null,
                        'failure_code' => 'activation_interrupted',
                        'failure_summary' => 'Laravel maintenance mode remained inactive after an interrupted activation.',
                        'failure_at' => now(),
                    ])->save();
                    $this->recordReconciliation($current, 'native_inactive_pending');
                    $current = null;
                }

                if ($current && ! $active && in_array($current->state, [
                    MaintenanceWindow::STATE_ACTIVE,
                    MaintenanceWindow::STATE_DEACTIVATION_PENDING,
                ], true)) {
                    $current->forceFill([
                        'state' => MaintenanceWindow::STATE_ENDED,
                        'active_guard' => null,
                        'ended_by' => $current->deactivation_requested_by,
                        'deactivated_at' => now(),
                    ])->save();
                    $this->recordReconciliation($current, 'native_inactive');
                    $current = null;
                }

                return $this->statusSnapshot($active, $current, null);
            });
        } catch (LockTimeoutException) {
            $this->audit->record(
                'maintenance.reconciliation_refused',
                auth()->user(),
                context: [
                    'action' => 'reconcile',
                    'reason_code' => 'transition_busy',
                ],
            );

            return $this->statusSnapshot(
                $this->maintenanceMode->active(),
                MaintenanceWindow::query()->whereNotNull('active_guard')->first(),
                'Maintenance history is busy. Refresh the console before attempting a transition.',
            );
        }
    }

    public function activate(
        User $actor,
        string $message,
        ?CarbonInterface $plannedEndAt,
    ): MaintenanceWindow {
        try {
            return $this->withLock(function () use ($actor, $message, $plannedEndAt): MaintenanceWindow {
                $currentRows = MaintenanceWindow::query()
                    ->whereNotNull('active_guard')
                    ->limit(2)
                    ->get();

                if ($currentRows->count() > 1) {
                    $this->audit->record('maintenance.activation_refused', $actor, context: [
                        'action' => 'activate',
                        'reason_code' => 'duplicate_current_history',
                    ]);

                    throw new MaintenanceTransitionException('duplicate_current_history');
                }

                if ($this->maintenanceMode->active() || $currentRows->isNotEmpty()) {
                    $this->audit->record('maintenance.activation_refused', $actor, context: [
                        'action' => 'activate',
                        'reason_code' => 'already_active',
                    ]);

                    throw new MaintenanceTransitionException('already_active');
                }

                $window = MaintenanceWindow::query()->create([
                    'message' => $message,
                    'planned_end_at' => $plannedEndAt,
                    'state' => MaintenanceWindow::STATE_PENDING,
                    'active_guard' => MaintenanceWindow::ACTIVE_GUARD,
                    'initiated_by' => $actor->id,
                ]);

                try {
                    $template = view('errors.maintenance', [
                        'message' => $window->message,
                        'plannedEndAt' => $window->planned_end_at,
                    ])->render();
                    $this->maintenanceMode->activate([
                        'status' => 503,
                        'retry' => 60,
                        'refresh' => 60,
                        'template' => $template,
                    ]);

                    if (! $this->maintenanceMode->active()) {
                        throw new MaintenanceTransitionException('activation_unverified');
                    }
                } catch (Throwable) {
                    try {
                        $this->maintenanceMode->deactivate();
                    } catch (Throwable) {
                        // The authoritative state is reconciled on the next request.
                    }

                    $window->forceFill([
                        'state' => MaintenanceWindow::STATE_ACTIVATION_FAILED,
                        'active_guard' => null,
                        'failure_code' => 'native_activation_failed',
                        'failure_summary' => 'Laravel maintenance mode could not be activated.',
                        'failure_at' => now(),
                    ])->save();
                    $this->audit->record('maintenance.activation_failed', $actor, $window, [
                        'action' => 'activate',
                        'reason_code' => 'native_activation_failed',
                        'state' => $window->state,
                        'window_id' => $window->id,
                    ]);

                    throw new MaintenanceTransitionException('native_activation_failed');
                }

                try {
                    $window->forceFill([
                        'state' => MaintenanceWindow::STATE_ACTIVE,
                        'activated_at' => now(),
                    ])->save();
                    $this->audit->record('maintenance.activation_succeeded', $actor, $window, [
                        'action' => 'activate',
                        'reason_code' => 'confirmed',
                        'state' => $window->state,
                        'window_id' => $window->id,
                    ]);
                } catch (Throwable) {
                    $this->audit->record('maintenance.history_finalization_failed', $actor, $window, [
                        'action' => 'activate',
                        'reason_code' => 'activation_history_update_failed',
                        'state' => MaintenanceWindow::STATE_PENDING,
                        'window_id' => $window->id,
                    ]);

                    throw new MaintenanceTransitionException('activation_history_update_failed');
                }

                return $window;
            });
        } catch (LockTimeoutException) {
            $this->audit->record('maintenance.activation_refused', $actor, context: [
                'action' => 'activate',
                'reason_code' => 'transition_busy',
            ]);

            throw new MaintenanceTransitionException('transition_busy');
        }
    }

    public function deactivate(User $actor): MaintenanceWindow
    {
        try {
            return $this->withLock(function () use ($actor): MaintenanceWindow {
                $currentRows = MaintenanceWindow::query()
                    ->whereNotNull('active_guard')
                    ->limit(2)
                    ->get();

                if ($currentRows->count() > 1) {
                    $this->audit->record('maintenance.deactivation_refused', $actor, context: [
                        'action' => 'deactivate',
                        'reason_code' => 'duplicate_current_history',
                    ]);

                    throw new MaintenanceTransitionException('duplicate_current_history');
                }

                $window = $currentRows->first();

                if (! $this->maintenanceMode->active() || ! $window) {
                    $this->audit->record('maintenance.deactivation_refused', $actor, context: [
                        'action' => 'deactivate',
                        'reason_code' => 'not_active',
                    ]);

                    throw new MaintenanceTransitionException('not_active');
                }

                $window->forceFill([
                    'state' => MaintenanceWindow::STATE_DEACTIVATION_PENDING,
                    'deactivation_requested_by' => $actor->id,
                    'deactivation_requested_at' => now(),
                ])->save();

                try {
                    $this->maintenanceMode->deactivate();

                    if ($this->maintenanceMode->active()) {
                        throw new MaintenanceTransitionException('deactivation_unverified');
                    }
                } catch (Throwable) {
                    $window->forceFill([
                        'state' => MaintenanceWindow::STATE_ACTIVE,
                        'failure_code' => 'native_deactivation_failed',
                        'failure_summary' => 'Laravel maintenance mode could not be deactivated.',
                        'failure_at' => now(),
                    ])->save();
                    $this->audit->record('maintenance.deactivation_failed', $actor, $window, [
                        'action' => 'deactivate',
                        'reason_code' => 'native_deactivation_failed',
                        'state' => $window->state,
                        'window_id' => $window->id,
                    ]);

                    throw new MaintenanceTransitionException('native_deactivation_failed');
                }

                try {
                    $window->forceFill([
                        'state' => MaintenanceWindow::STATE_ENDED,
                        'active_guard' => null,
                        'ended_by' => $actor->id,
                        'deactivated_at' => now(),
                        'failure_code' => null,
                        'failure_summary' => null,
                        'failure_at' => null,
                    ])->save();
                    $this->audit->record('maintenance.deactivation_succeeded', $actor, $window, [
                        'action' => 'deactivate',
                        'reason_code' => 'confirmed',
                        'state' => $window->state,
                        'window_id' => $window->id,
                    ]);
                } catch (Throwable) {
                    $this->audit->record('maintenance.history_finalization_failed', $actor, $window, [
                        'action' => 'deactivate',
                        'reason_code' => 'deactivation_history_update_failed',
                        'state' => MaintenanceWindow::STATE_DEACTIVATION_PENDING,
                        'window_id' => $window->id,
                    ]);

                    throw new MaintenanceTransitionException('deactivation_history_update_failed');
                }

                return $window;
            });
        } catch (LockTimeoutException) {
            $this->audit->record('maintenance.deactivation_refused', $actor, context: [
                'action' => 'deactivate',
                'reason_code' => 'transition_busy',
            ]);

            throw new MaintenanceTransitionException('transition_busy');
        }
    }

    private function withLock(callable $operation): mixed
    {
        $store = config('app.maintenance.driver') === 'cache'
            ? config('app.maintenance.store')
            : config('cache.default');

        return Cache::store($store)
            ->lock('myapes:maintenance-transition', 10)
            ->block(
                max(0, (int) config('maintenance.lock_wait_seconds', 5)),
                $operation,
            );
    }

    private function recordReconciliation(
        MaintenanceWindow $window,
        string $reasonCode,
    ): void {
        $this->audit->record('maintenance.history_reconciled', null, $window, [
            'action' => 'reconcile',
            'reason_code' => $reasonCode,
            'state' => $window->state,
            'window_id' => $window->id,
        ]);
    }

    /** @return array{active: bool, current: MaintenanceWindow|null, history: Collection<int, MaintenanceWindow>, problem: string|null} */
    private function statusSnapshot(
        bool $active,
        ?MaintenanceWindow $current,
        ?string $problem,
    ): array {
        return [
            'active' => $active,
            'current' => $current?->load([
                'initiator',
                'deactivationRequester',
                'endingActor',
            ]),
            'history' => MaintenanceWindow::query()
                ->with(['initiator', 'deactivationRequester', 'endingActor'])
                ->latest('id')
                ->limit(25)
                ->get(),
            'problem' => $problem,
        ];
    }
}
