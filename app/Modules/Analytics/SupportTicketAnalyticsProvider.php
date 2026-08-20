<?php

namespace App\Modules\Analytics;

use App\Contracts\ModuleAnalyticsProvider;
use App\Models\SupportTicket;
use App\Modules\ModuleAnalyticsSnapshot;
use App\Modules\ModuleInstanceDefinition;
use App\Services\ModuleState;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class SupportTicketAnalyticsProvider implements ModuleAnalyticsProvider
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
        $created = SupportTicket::query()
            ->forSubCore($instance->subCore->key)
            ->where('created_at', '>=', $fromBoundary)
            ->where('created_at', '<', $toBoundary)
            ->get();
        $closed = SupportTicket::query()
            ->forSubCore($instance->subCore->key)
            ->whereNotNull('closed_at')
            ->where('closed_at', '>=', $fromBoundary)
            ->where('closed_at', '<', $toBoundary)
            ->get();
        $open = $created->whereNotIn('status', ['resolved', 'closed']);
        $currentlyOpen = SupportTicket::query()
            ->forSubCore($instance->subCore->key)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->get();
        $closureMinutes = $this->closureMinutes($closed->all());

        return new ModuleAnalyticsSnapshot(
            $instance->key(),
            $created->count(),
            $open->count(),
            $open->whereIn('priority', ['high', 'urgent'])->count(),
            $open->whereNull('assigned_to')->count(),
            $this->perDay($created->all(), 'created_at', $timezone),
            $this->perDay($closed->all(), 'closed_at', $timezone),
            $this->median($closureMinutes),
            $closed->count(),
            $currentlyOpen->count(),
            $currentlyOpen->whereIn('priority', ['high', 'urgent'])->count(),
            $currentlyOpen->whereNull('assigned_to')->count(),
            $closureMinutes,
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

    /** @param array<int, SupportTicket> $records */
    private function closureMinutes(array $records): array
    {
        return array_map(
            static fn (SupportTicket $ticket): float => $ticket->created_at
                ->diffInMinutes($ticket->closed_at),
            $records,
        );
    }

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
