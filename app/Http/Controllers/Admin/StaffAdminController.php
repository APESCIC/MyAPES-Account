<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthorizationProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class StaffAdminController extends Controller
{
    public function __invoke(AuthorizationProfile $profile): View
    {
        return view('admin.index', [
            'totalUsers' => User::count(),
            'staffUsers' => User::withAuthorizationPermission(
                AuthorizationProfile::PERMISSION_STAFF_ACCESS,
            )->count(),
            'adminUsers' => User::withAuthorizationPermission(
                AuthorizationProfile::PERMISSION_ADMIN_ACCESS,
            )->count(),
            'recentUsers' => Gate::allows('admin.users.view')
                ? User::latest()->limit(10)->get()
                : collect(),
            'authorizationProfile' => $profile,
        ]);
    }
}
