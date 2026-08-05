<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AdminPermissionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $query = Permission::query()
            ->where('guard_name', 'web')
            ->where('is_code_owned', true)
            ->with([
                'roles' => fn ($query) => $query->orderBy('name'),
            ]);

        if (isset($filters['q'])) {
            $query->where(
                'name',
                'like',
                '%'.trim($filters['q']).'%',
            );
        }

        return view('admin.permissions.index', [
            'permissions' => $query
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString(),
            'filters' => $filters,
        ]);
    }
}
