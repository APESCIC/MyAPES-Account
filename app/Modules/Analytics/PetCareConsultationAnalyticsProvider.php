<?php

namespace App\Modules\Analytics;

use App\Contracts\ModuleAnalyticsProvider;
use App\Models\PetCareConsultation;
use App\Modules\ModuleAnalyticsSnapshot;
use App\Modules\ModuleInstanceDefinition;
use App\Services\ModuleState;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class PetCareConsultationAnalyticsProvider implements ModuleAnalyticsProvider
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
        $created = PetCareConsultation::query()
            ->forPetCareDomain()
            ->where('created_at', '>=', $fromBoundary)
            ->where('created_at', '<', $toBoundary)
            ->get();
        $closed = PetCareConsultation::query()
            ->forPetCareDomain()
            ->whereNotNull('closed_at')
            ->where('closed_at', '>=', $fromBoundary)
            ->where('closed_at', '<', $toBoundary)
            ->get();
        $open = $created->filter(
            static fn (PetCareConsultation $consultation): bool => $consultation->status !== 'closed'
                && $consultation->closed_at === null,
        );

        return new ModuleAnalyticsSnapshot(
            $instance->key(),
            $created->count(),
            $open->count(),
            0,
            $open->whereNull('assigned_to')->count(),
            $this->perDay($created->all(), 'created_at', $timezone),
            $this->perDay($closed->all(), 'closed_at', $timezone),
            $this->medianClosureMinutes($closed->all()),
            $closed->count(),
        );
    }

    /** @param array<int, PetCareConsultation> $records */
    private function perDay(
        array $records,
        string $field,
        string $timezone,
    ): array {
        $counts = [];
        foreach ($records as $record) {
            $day = Carbon::parse($record->{$field})
                ->setTimezone($timezone)
                ->toDateString();
            $counts[$day] = ($counts[$day] ?? 0) + 1;
        }
        ksort($counts);

        return $counts;
    }

    /** @param array<int, PetCareConsultation> $records */
    private function medianClosureMinutes(array $records): ?float
    {
        if ($records === []) {
            return null;
        }

        $durations = array_map(
            static fn (PetCareConsultation $consultation): float => $consultation->created_at
                ->diffInMinutes($consultation->closed_at),
            $records,
        );
        sort($durations, SORT_NUMERIC);
        $middle = intdiv(count($durations), 2);

        return count($durations) % 2 === 1
            ? (float) $durations[$middle]
            : ($durations[$middle - 1] + $durations[$middle]) / 2;
    }
}
