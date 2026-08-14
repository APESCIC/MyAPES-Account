<?php

namespace App\Http\Controllers;

use App\Services\ModuleDashboardAttentionService;
use App\Services\ModuleDashboardSummaryService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        ModuleDashboardSummaryService $summaries,
        ModuleDashboardAttentionService $attention,
    ): View {
        $user = request()->user();

        return view('dashboard', [
            'moduleSummaries' => $summaries->forUser($user),
            'attentionItems' => $attention->forUser($user),
        ]);
    }
}
