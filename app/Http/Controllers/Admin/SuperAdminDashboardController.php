<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAnalyticsAggregator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SuperAdminDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        AdminAnalyticsAggregator $analytics,
    ): View {
        $range = $analytics->normalizeRange($request->query('range'));

        return view('superadmin.index', [
            'range' => $range,
            'ranges' => AdminAnalyticsAggregator::RANGES,
            'dashboard' => $analytics->dashboard($range),
        ]);
    }
}
