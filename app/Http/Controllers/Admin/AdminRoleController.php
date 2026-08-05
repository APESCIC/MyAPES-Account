<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuthorizationProfile;
use App\Services\AuthorizationRoleManagementService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class AdminRoleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $query = Role::query()
            ->where('guard_name', 'web')
            ->withCount(['permissions', 'users']);

        if (isset($filters['q'])) {
            $query->where(
                'name',
                'like',
                '%'.trim($filters['q']).'%',
            );
        }

        return view('admin.roles.index', [
            'roles' => $query
                ->orderBy('is_protected', 'desc')
                ->orderBy('name')
                ->paginate(25)
                ->withQueryString(),
            'permissions' => Permission::query()
                ->where('guard_name', 'web')
                ->where('is_code_owned', true)
                ->orderBy('name')
                ->get(),
            'filters' => $filters,
        ]);
    }

    public function show(string $role): View
    {
        Gate::authorize('admin.roles.view');
        $managedRole = Role::query()
            ->where('guard_name', 'web')
            ->findOrFail($role);
        $managedRole->load([
            'permissions' => fn ($query) => $query->orderBy('name'),
        ])->loadCount([
            'users',
            'permissions',
        ]);

        return view('admin.roles.show', [
            'managedRole' => $managedRole,
            'permissions' => Permission::query()
                ->where('guard_name', 'web')
                ->where('is_code_owned', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(
        Request $request,
        AuthorizationRoleManagementService $roles,
        AuthorizationProfile $profile,
    ): RedirectResponse {
        $validated = $this->validateRole($request, $profile);

        try {
            $role = $roles->create(
                $request->user(),
                $validated['name'],
                $validated['permissions'] ?? [],
            );
        } catch (DomainException|InvalidArgumentException $exception) {
            return back()->withErrors([
                'authorization' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('status', 'Custom role created.');
    }

    public function update(
        Request $request,
        string $role,
        AuthorizationRoleManagementService $roles,
        AuthorizationProfile $profile,
    ): RedirectResponse {
        Gate::authorize('admin.roles.manage');
        $managedRole = Role::query()
            ->where('guard_name', 'web')
            ->findOrFail($role);
        $validated = $this->validateRole(
            $request,
            $profile,
            $managedRole,
        );

        try {
            $roles->update(
                $request->user(),
                $managedRole,
                $validated['name'],
                $validated['permissions'] ?? [],
            );
        } catch (DomainException|InvalidArgumentException $exception) {
            return back()->withErrors([
                'authorization' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.roles.show', $role)
            ->with('status', 'Custom role updated.');
    }

    public function destroy(
        Request $request,
        string $role,
        AuthorizationRoleManagementService $roles,
    ): RedirectResponse {
        Gate::authorize('admin.roles.manage');
        $managedRole = Role::query()
            ->where('guard_name', 'web')
            ->findOrFail($role);

        try {
            $roles->delete($request->user(), $managedRole);
        } catch (DomainException|InvalidArgumentException $exception) {
            return back()->withErrors([
                'authorization' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('status', 'Custom role deleted.');
    }

    /**
     * @return array{name: string, permissions?: array<int, string>}
     */
    private function validateRole(
        Request $request,
        AuthorizationProfile $profile,
        ?Role $role = null,
    ): array {
        $unique = Rule::unique('roles', 'name')
            ->where('guard_name', 'web');

        if ($role !== null) {
            $unique->ignore($role->id);
        }

        return $request->validate([
            'name' => [
                'required',
                'string',
                'min:3',
                'max:64',
                'regex:/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/',
                Rule::notIn($profile->protectedRolesByPrecedence()),
                $unique,
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [
                'string',
                'distinct',
                Rule::in($profile->permissions()),
            ],
        ]);
    }
}
