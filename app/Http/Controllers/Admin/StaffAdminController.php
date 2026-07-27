<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;

class StaffAdminController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.index', [
            'totalUsers' => User::count(),
            'staffUsers' => User::withAccessLevels(User::staffAccessLevels())->count(),
            'adminUsers' => User::withAccessLevels(User::adminAccessLevels())->count(),
            'recentUsers' => User::latest()->limit(10)->get(),
        ]);
    }
}
