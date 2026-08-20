<?php

namespace App\Modules\Analytics;

use App\Contracts\ModuleAnalyticsProvider;
use App\Models\PetProfile;
use App\Models\ShelterCase;
use App\Modules\ModuleAnalyticsSnapshot;
use App\Modules\ModuleInstanceDefinition;
use App\Services\ModuleState;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class CaseAnalyticsProvider implements ModuleAnalyticsProvider
{
    public function __construct(
        private readonly ModuleState $modules,
    ) {}

    public function snapshot(
        ModuleInstanceDefinition $instance,
        DateTimeInterface $from,
        DateTimeInterface $to,
        string $timezone,
    ): ModuleAnalyticsSnapshot {
        if (! $this->modules->enabled(
            $instance->subCore->key,
            $instance->module->key,
        )) {
            return ModuleAnalyticsSnapshot::empty($instance->key());
        }

        $fromBoundary = Carbon::instance($from)
            ->setTimezone(config('app.timezone'));
        $toBoundary = Carbon::instance($to)
            ->setTimezone(config('app.timezone'));
        $created = $this->caseQuery($instance)
            ->where('created_at', '>=', $fromBoundary)
            ->where('created_at', '<', $toBoundary)
            ->get();
        $terminalField = 'closed_at';
        $closedQuery = $this->caseQuery($instance);
        if ($instance->subCore->key === ShelterCase::SUB_CORE_APES_CIC) {
            $terminalField = 'terminal_at';
            $closedQuery
                ->selectRaw('shelter_cases.*, COALESCE(resolved_at, closed_at) as terminal_at')
                ->whereRaw('COALESCE(resolved_at, closed_at) >= ?', [$fromBoundary])
                ->whereRaw('COALESCE(resolved_at, closed_at) < ?', [$toBoundary]);
        } else {
            $closedQuery
                ->whereNotNull('closed_at')
                ->where('closed_at', '>=', $fromBoundary)
                ->where('closed_at', '<', $toBoundary);
        }
        $closed = $closedQuery->get();
        $open = $created->whereNotIn('status', ['resolved', 'closed']);
        $currentlyOpen = $this->caseQuery($instance)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->get();
        $closureMinutes = $this->closureMinutes($closed->all(), $terminalField);

        return new ModuleAnalyticsSnapshot(
            $instance->key(),
            $created->count(),
            $open->count(),
            $open->whereIn('priority', ['high', 'urgent'])->count(),
            $open->whereNull('assigned_to')->count(),
            $this->perDay($created->all(), 'created_at', $timezone),
            $this->perDay($closed->all(), $terminalField, $timezone),
            $this->median($closureMinutes),
            $closed->count(),
            $currentlyOpen->count(),
            $currentlyOpen->whereIn('priority', ['high', 'urgent'])->count(),
            $currentlyOpen->whereNull('assigned_to')->count(),
            $closureMinutes,
        );
    }

    private function caseQuery(ModuleInstanceDefinition $instance): Builder
    {
        $query = ShelterCase::query()
            ->forSubCore($instance->subCore->key);
        if ($instance->subCore->key === ShelterCase::SUB_CORE_SHELTER_RESCUE) {
            $query->whereHas(
                'petProfile',
                static fn ($pets) => $pets->where(
                    'service_domain',
                    PetProfile::DOMAIN_SHELTER,
                ),
            );
        }

        return $query;
    }

    private function perDay(array $records, string $field, string $timezone): array
    {
        $counts = [];
        foreach ($records as $record) {
            $day = Carbon::parse($record->{$field})->setTimezone($timezone)->toDateString();
            $counts[$day] = ($counts[$day] ?? 0) + 1;
        }
        ksort($counts);

        return $counts;
    }

    /** @param array<int, ShelterCase> $records */
    private function closureMinutes(array $records, string $terminalField): array
    {
        return array_map(
            static fn (ShelterCase $case): float => ($case->opened_at ?? $case->created_at)
                ->diffInMinutes(Carbon::parse($case->{$terminalField})),
            $records,
        );
    }

    /** @param array<int, float> $values */
    private function median(array $values): ?float
    {
        if ($values === []) {
            return null;
        }
        sort($values, SORT_NUMERIC);
        $middle = intdiv(count($values), 2);

        return count($values) % 2 === 1
            ? (float) $values[$middle]
            : ($values[$middle - 1] + $values[$middle]) / 2;
    }
}
