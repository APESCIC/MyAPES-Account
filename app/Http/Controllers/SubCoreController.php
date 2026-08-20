<?php

namespace App\Http\Controllers;

use App\Contracts\ModuleNavigationProvider;
use App\Contracts\ModuleRecentActivityProvider;
use App\Contracts\ModuleRegistry;
use App\Services\ModuleDashboardSummaryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SubCoreController extends Controller
{
    public function show(
        Request $request,
        ModuleRegistry $registry,
        ModuleNavigationProvider $navigation,
        ModuleDashboardSummaryService $summaries,
        string $subCoreKey,
    ): View {
        $user = $request->user();
        $modules = $navigation->forSubCore($user, $subCoreKey);
        $summaryByInstance = collect($summaries->forUser($user))
            ->flatMap(static fn ($group) => $group->summaries)
            ->keyBy('instanceKey');
        $activity = collect();

        foreach ($modules as $module) {
            $instance = $registry->instance($subCoreKey, $module->moduleKey);
            $providerClass = $instance->recentActivityProviderClass();
            if ($providerClass === null) {
                continue;
            }

            /** @var ModuleRecentActivityProvider $provider */
            $provider = app($providerClass);
            $activity = $activity->concat($provider->recent($instance, $user, 5));
        }

        return view('sub-cores.show', [
            'subCore' => $registry->subCore($subCoreKey),
            'modules' => $modules,
            'moduleSummaries' => $summaryByInstance,
            'recentActivity' => $activity
                ->sortByDesc(fn ($item) => $item->updatedAt->getTimestamp())
                ->take(5)
                ->values(),
        ]);
    }
}
