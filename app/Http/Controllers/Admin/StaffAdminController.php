<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminAnalyticsAggregator;
use App\Services\AuthorizationProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StaffAdminController extends Controller
{
    public function __invoke(
        Request $request,
        AdminAnalyticsAggregator $analytics,
        AuthorizationProfile $profile,
    ): View {
        $range = $analytics->normalizeRange($request->query('range'));

        return view('admin.index', [
            'range' => $range,
            'ranges' => AdminAnalyticsAggregator::RANGES,
            'dashboard' => $analytics->dashboard($range),
            'recentUsers' => Gate::allows('admin.users.view')
                ? User::latest()->limit(10)->get()
                : collect(),
            'authorizationProfile' => $profile,
        ]);
    }
}
