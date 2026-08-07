<?php

namespace App\Services;

use App\Models\ModuleInstallation;
use Illuminate\Support\Facades\Cache;

class ModuleCatalogueProjection
{
    public function __construct(
        private readonly ModuleProjectionCache $versions,
    ) {}

    /** @return array<int, string> */
    public function enabledInstanceKeys(): array
    {
        $version = $this->versions->version();
        $seconds = max(
            1,
            (int) config('modules.projection_cache_seconds', 30),
        );

        return Cache::remember(
            "myapes:modules:enabled:v{$version}",
            $seconds,
            static fn (): array => ModuleInstallation::query()
                ->where('enabled', true)
                ->orderBy('sub_core_key')
                ->orderBy('module_key')
                ->get()
                ->map->instanceKey()
                ->all(),
        );
    }
}
