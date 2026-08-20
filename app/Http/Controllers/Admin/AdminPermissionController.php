<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Services\AuthorizationProfile;
use App\Support\PermissionDescriptions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AdminPermissionController extends Controller
{
    public function index(Request $request, AuthorizationProfile $profile): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'group' => ['nullable', 'string', 'max:64'],
        ]);

        $query = Permission::query()
            ->where('guard_name', 'web')
            ->where('is_code_owned', true)
            ->with([
                'roles' => fn ($builder) => $builder->orderBy('name'),
            ]);

        if (isset($filters['q']) && trim($filters['q']) !== '') {
            $needle = trim($filters['q']);
            $matchedKeys = PermissionDescriptions::matchingKeys($needle);
            $query->where(function ($builder) use ($needle, $matchedKeys): void {
                $builder->where('name', 'like', '%'.$needle.'%');
                if ($matchedKeys !== []) {
                    $builder->orWhereIn('name', $matchedKeys);
                }
            });
        }

        $allMatching = $query->orderBy('name')->get();

        if (isset($filters['group']) && $filters['group'] !== '') {
            $group = $filters['group'];
            $allMatching = $allMatching->filter(
                fn (Permission $permission): bool => PermissionDescriptions::group($permission->name) === $group,
            )->values();
        }

        $page = max(1, (int) $request->query('page', 1));
        $perPage = 50;
        $permissions = new \Illuminate\Pagination\LengthAwarePaginator(
            $allMatching->forPage($page, $perPage)->values(),
            $allMatching->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ],
        );

        return view('admin.permissions.index', [
            'permissions' => $permissions,
            'filters' => $filters,
            'groups' => PermissionDescriptions::groupsFor($profile->permissions()),
        ]);
    }
}
