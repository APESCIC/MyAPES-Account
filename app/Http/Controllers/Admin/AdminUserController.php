<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\RoleSource;
use App\Models\User;
use App\Services\AuthorizationMutationService;
use App\Services\AuthorizationProfile;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class AdminUserController extends Controller
{
    public function index(
        Request $request,
        AuthorizationProfile $profile,
    ): View {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'identity_type' => [
                'nullable',
                Rule::in([
                    User::IDENTITY_LOCAL,
                    User::IDENTITY_CLOUDRON_OIDC,
                    User::IDENTITY_HYBRID,
                ]),
            ],
            'status' => ['nullable', Rule::in(['active', 'suspended'])],
            'protected_role' => [
                'nullable',
                Rule::in($profile->protectedRolesByPrecedence()),
            ],
        ]);
        $query = User::query()->with([
            'roles' => fn ($query) => $query->orderBy('name'),
        ]);

        if (isset($filters['q'])) {
            $search = trim($filters['q']);
            $query->where(static function ($query) use ($search): void {
                $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($filters['identity_type'])) {
            $query->where('identity_type', $filters['identity_type']);
        }

        if (($filters['status'] ?? null) === 'active') {
            $query->whereNull('suspended_at');
        } elseif (($filters['status'] ?? null) === 'suspended') {
            $query->whereNotNull('suspended_at');
        }

        if (isset($filters['protected_role'])) {
            $role = $filters['protected_role'];
            $query->whereHas(
                'roles',
                static fn ($query) => $query
                    ->where('roles.guard_name', 'web')
                    ->where('roles.name', $role),
            );
        }

        return view('admin.users.index', [
            'users' => $query
                ->orderBy('name')
                ->orderBy('id')
                ->paginate(25)
                ->withQueryString(),
            'filters' => $filters,
            'identityTypes' => [
                User::IDENTITY_LOCAL => 'Local',
                User::IDENTITY_CLOUDRON_OIDC => 'Cloudron OIDC',
                User::IDENTITY_HYBRID => 'Hybrid',
            ],
            'protectedRoles' => $profile->protectedRolesByPrecedence(),
            'authorizationProfile' => $profile,
        ]);
    }

    public function show(
        Request $request,
        string $user,
        AuthorizationMutationService $mutations,
    ): View {
        Gate::authorize('admin.users.view');
        $managedUser = User::query()->findOrFail($user);
        $managedUser->load([
            'permissions' => fn ($query) => $query->orderBy('name'),
            'permissionSources' => fn ($query) => $query
                ->with(['permission', 'actor'])
                ->orderBy('permission_id')
                ->orderBy('source'),
            'roles' => fn ($query) => $query
                ->with('permissions')
                ->orderBy('name'),
            'roleSources' => fn ($query) => $query
                ->with(['role', 'directoryGroup'])
                ->orderBy('role_id')
                ->orderBy('source'),
        ]);
        $permissions = $managedUser->permissions
            ->merge($managedUser->roles->flatMap->permissions)
            ->unique('id')
            ->sortBy('name')
            ->values();
        $auditContextKeys = [
            'actor_id',
            'target_user_id',
            'role_id',
            'role_ids',
            'role_count',
            'permission_count',
            'affected_user_count',
            'group_id',
            'mapping_id',
            'matched_user_count',
            'changed_user_count',
            'action',
            'source_key',
            'route_name',
            'method',
            'reason_code',
            'reason_length',
            'granted_count',
            'revoked_count',
        ];
        $auditHistory = AuditLog::query()
            ->where('auditable_type', $managedUser->getMorphClass())
            ->where('auditable_id', $managedUser->id)
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(static fn (AuditLog $audit): array => [
                'event' => $audit->event,
                'actor_id' => $audit->user_id,
                'created_at' => $audit->created_at,
                'context' => Arr::only(
                    is_array($audit->context) ? $audit->context : [],
                    $auditContextKeys,
                ),
            ]);

        return view('admin.users.show', [
            'managedUser' => $managedUser,
            'permissions' => $permissions,
            'customRoles' => Role::query()
                ->where('guard_name', 'web')
                ->where('is_protected', false)
                ->orderBy('name')
                ->get(),
            'localRoleIds' => $managedUser->roleSources
                ->where('source', RoleSource::SOURCE_LOCAL)
                ->pluck('role_id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all(),
            'auditHistory' => $auditHistory,
            'canManageTarget' => $mutations->canManageTarget(
                $request->user(),
                $managedUser,
            ),
            'identityLabel' => match ($managedUser->identity_type) {
                User::IDENTITY_LOCAL => 'Local',
                User::IDENTITY_CLOUDRON_OIDC => 'Cloudron OIDC',
                User::IDENTITY_HYBRID => 'Hybrid',
                default => 'Unknown',
            },
        ]);
    }

    public function updateRoles(
        Request $request,
        string $user,
        AuthorizationMutationService $mutations,
    ): RedirectResponse {
        Gate::authorize('admin.users.manage');
        $managedUser = User::query()->findOrFail($user);
        $validated = $request->validate([
            'roles' => ['sometimes', 'array'],
            'roles.*' => [
                'integer',
                'distinct',
                Rule::exists('roles', 'id')->where('guard_name', 'web'),
            ],
        ]);
        $roleIds = array_map(
            'intval',
            $validated['roles'] ?? [],
        );
        $roles = Role::query()->whereKey($roleIds)->get()->all();

        try {
            $mutations->synchronizeLocalRoles(
                $managedUser,
                $roles,
                $request->user(),
            );
        } catch (DomainException|InvalidArgumentException $exception) {
            return back()->withErrors([
                'authorization' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'Local role assignments updated.');
    }

    public function suspend(
        Request $request,
        string $user,
        AuthorizationMutationService $mutations,
    ): RedirectResponse {
        Gate::authorize('admin.users.manage');
        $managedUser = User::query()->findOrFail($user);
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        try {
            $mutations->suspend(
                $managedUser,
                $request->user(),
                $validated['reason'],
            );
        } catch (DomainException|InvalidArgumentException $exception) {
            return back()->withErrors([
                'authorization' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'User suspended.');
    }

    public function reactivate(
        Request $request,
        string $user,
        AuthorizationMutationService $mutations,
    ): RedirectResponse {
        Gate::authorize('admin.users.manage');
        $managedUser = User::query()->findOrFail($user);

        try {
            $mutations->reactivate($managedUser, $request->user());
        } catch (DomainException $exception) {
            return back()->withErrors([
                'authorization' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'User reactivated.');
    }
}
