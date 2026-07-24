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
            'staffUsers' => User::whereIn('role', [User::ROLE_STAFF, User::ROLE_ADMIN, User::ROLE_SUPERADMIN])->count(),
            'adminUsers' => User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPERADMIN])->count(),
            'recentUsers' => User::latest()->limit(10)->get(),
        ]);
    }
}
