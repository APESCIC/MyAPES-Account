<?php

namespace App\Services;

use App\Models\ModuleInstallation;
use Illuminate\Support\Facades\Cache;

class ModuleCatalogueProjection
{
    private const REQUEST_ATTRIBUTE = 'myapes.modules.enabled-instance-keys';

    public function __construct(
        private readonly ModuleProjectionCache $versions,
    ) {}

    /** @return array<int, string> */
    public function enabledInstanceKeys(): array
    {
        $request = request();
        if ($request->route() !== null
            && $request->attributes->has(self::REQUEST_ATTRIBUTE)) {
            return $request->attributes->get(self::REQUEST_ATTRIBUTE);
        }

        $version = $this->versions->version();
        $seconds = max(
            1,
            (int) config('modules.projection_cache_seconds', 30),
        );
        $currentlyEnabled = ModuleInstallation::query()
            ->where('enabled', true)
            ->orderBy('sub_core_key')
            ->orderBy('module_key')
            ->get()
            ->map->instanceKey()
            ->all();

        $cacheKey = "myapes:modules:enabled:v{$version}";
        $projected = Cache::remember(
            $cacheKey,
            $seconds,
            static fn (): array => $currentlyEnabled,
        );
        if ($projected !== $currentlyEnabled) {
            Cache::put($cacheKey, $currentlyEnabled, $seconds);
        }
        if ($request->route() !== null) {
            $request->attributes->set(
                self::REQUEST_ATTRIBUTE,
                $currentlyEnabled,
            );
        }

        return $currentlyEnabled;
    }
}
