<?php

namespace App\Modules\Analytics;

use App\Contracts\ModuleAnalyticsProvider;
use App\Models\ShelterCase;
use App\Modules\ModuleAnalyticsSnapshot;
use App\Modules\ModuleInstanceDefinition;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class CaseAnalyticsProvider implements ModuleAnalyticsProvider
{
    public function snapshot(
        ModuleInstanceDefinition $instance,
        DateTimeInterface $from,
        DateTimeInterface $to,
        string $timezone,
    ): ModuleAnalyticsSnapshot {
        $fromBoundary = Carbon::instance($from)
            ->setTimezone(config('app.timezone'));
        $toBoundary = Carbon::instance($to)
            ->setTimezone(config('app.timezone'));
        $created = ShelterCase::query()
            ->forSubCore($instance->subCore->key)
            ->where('created_at', '>=', $fromBoundary)
            ->where('created_at', '<', $toBoundary)
            ->get();
        $closed = ShelterCase::query()
            ->forSubCore($instance->subCore->key)
            ->whereNotNull('closed_at')
            ->where('closed_at', '>=', $fromBoundary)
            ->where('closed_at', '<', $toBoundary)
            ->get();
        $open = $created->whereNotIn('status', ['resolved', 'closed']);

        return new ModuleAnalyticsSnapshot(
            $instance->key(),
            $created->count(),
            $open->count(),
            $open->whereIn('priority', ['high', 'urgent'])->count(),
            $open->whereNull('assigned_to')->count(),
            $this->perDay($created->all(), 'created_at', $timezone),
            $this->perDay($closed->all(), 'closed_at', $timezone),
            $this->medianMinutes($closed->all()),
            $closed->count(),
        );
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

    private function medianMinutes(array $records): ?float
    {
        $durations = array_map(
            static fn (ShelterCase $case): float => ($case->opened_at ?? $case->created_at)
                ->diffInMinutes($case->closed_at),
            $records,
        );
        if ($durations === []) {
            return null;
        }
        sort($durations, SORT_NUMERIC);
        $middle = intdiv(count($durations), 2);

        return count($durations) % 2 === 1
            ? (float) $durations[$middle]
            : ($durations[$middle - 1] + $durations[$middle]) / 2;
    }
}
