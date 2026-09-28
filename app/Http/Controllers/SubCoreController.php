<?php

namespace App\Http\Controllers;

use App\Contracts\ModuleNavigationProvider;
use App\Contracts\ModuleRecentActivityProvider;
use App\Contracts\ModuleRegistry;
use App\Core\Extensions\Modules\ModulePackageRegistry;
use App\Services\ModuleDashboardAttentionService;
use App\Services\ModuleDashboardSummaryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SubCoreController extends Controller
{
    public function show(
        Request $request,
        ModuleRegistry $registry,
        ModulePackageRegistry $modulePackages,
        ModuleNavigationProvider $navigation,
        ModuleDashboardSummaryService $summaries,
        ModuleDashboardAttentionService $attention,
        string $subCoreKey,
    ): View {
        $user = $request->user();
        $modules = $navigation->forSubCore($user, $subCoreKey);
        $summaryGroup = $summaries->groupForUserInSubCore($user, $subCoreKey);
        $summaryByInstance = collect($summaryGroup?->summaries ?? [])
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

        $hubView = 'sub-cores.show';
        if ($modulePackages->has($subCoreKey)) {
            $candidate = $modulePackages->module($subCoreKey)->hubView;
            if (view()->exists($candidate)) {
                $hubView = $candidate;
            }
        }

        return view($hubView, [
            'subCore' => $registry->subCore($subCoreKey),
            'modules' => $modules,
            'summaryGroup' => $summaryGroup,
            'moduleSummaries' => $summaryByInstance,
            'attentionItems' => $attention->forUserInSubCore($user, $subCoreKey),
            'recentActivity' => $activity
                ->sortByDesc(fn ($item) => $item->updatedAt->getTimestamp())
                ->take(5)
                ->values(),
        ]);
    }
}
