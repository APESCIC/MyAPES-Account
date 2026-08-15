<?php

namespace App\Modules\Analytics;

use App\Contracts\ModuleAnalyticsProvider;
use App\Models\PetProfile;
use App\Modules\ModuleAnalyticsSnapshot;
use App\Modules\ModuleInstanceDefinition;
use App\Services\ModuleState;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class PetProfileAnalyticsProvider implements ModuleAnalyticsProvider
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

        $domain = match ($instance->subCore->key) {
            'shelter-rescue' => PetProfile::DOMAIN_SHELTER,
            'pet-care-clinic' => PetProfile::DOMAIN_PETCARE,
            default => throw new \LogicException(
                'Pet Profiles analytics requested for an incompatible sub-core.',
            ),
        };
        $fromBoundary = Carbon::instance($from)
            ->setTimezone(config('app.timezone'));
        $toBoundary = Carbon::instance($to)
            ->setTimezone(config('app.timezone'));
        $profiles = PetProfile::query()
            ->where('service_domain', $domain)
            ->where('created_at', '>=', $fromBoundary)
            ->where('created_at', '<', $toBoundary)
            ->get();
        $createdPerDay = [];
        foreach ($profiles as $profile) {
            $day = $profile->created_at
                ->copy()
                ->setTimezone($timezone)
                ->toDateString();
            $createdPerDay[$day] = ($createdPerDay[$day] ?? 0) + 1;
        }
        ksort($createdPerDay);

        return new ModuleAnalyticsSnapshot(
            $instance->key(),
            $profiles->count(),
            0,
            0,
            0,
            $createdPerDay,
            [],
            null,
            0,
        );
    }
}
