<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunDirectorySync;
use App\Models\DirectoryGroup;
use App\Models\DirectoryGroupRoleMapping;
use App\Models\Role;
use App\Services\AuditLogger;
use App\Services\DirectoryGroupMappingService;
use App\Services\ManualDirectorySyncQueueResolver;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use LogicException;

class AdminGroupController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => [
                'nullable',
                Rule::in([
                    DirectoryGroup::STATUS_PRESENT,
                    DirectoryGroup::STATUS_MISSING,
                ]),
            ],
            'mapped' => ['nullable', Rule::in(['0', '1'])],
            'app_enabled' => ['nullable', Rule::in(['0', '1'])],
        ]);
        $query = DirectoryGroup::query()->with([
            'roles' => fn ($query) => $query->orderBy('name'),
        ]);

        if (isset($filters['q'])) {
            $query->where(
                'name',
                'like',
                '%'.trim($filters['q']).'%',
            );
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (($filters['mapped'] ?? null) === '1') {
            $query->whereHas('roles');
        } elseif (($filters['mapped'] ?? null) === '0') {
            $query->whereDoesntHave('roles');
        }

        if (($filters['app_enabled'] ?? null) === '1') {
            $query->where('app_enabled', true);
        } elseif (($filters['app_enabled'] ?? null) === '0') {
            $query->where('app_enabled', false);
        }

        return view('admin.groups.index', [
            'groups' => $query
                ->orderBy('name')
                ->orderBy('id')
                ->paginate(25)
                ->withQueryString(),
            'roles' => Role::query()
                ->where('guard_name', 'web')
                ->orderBy('is_protected', 'desc')
                ->orderBy('name')
                ->get(),
            'filters' => $filters,
        ]);
    }

    public function sync(
        Request $request,
        AuditLogger $auditLogger,
        ManualDirectorySyncQueueResolver $queueResolver,
    ): RedirectResponse {
        try {
            $connection = $queueResolver->resolve();
        } catch (DomainException $exception) {
            return back()->withErrors([
                'authorization' => $exception->getMessage(),
            ]);
        }

        RunDirectorySync::dispatch()
            ->onConnection($connection);
        $actor = $request->user();
        $auditLogger->record(
            'authorization.directory_sync_requested',
            $actor,
            null,
            [
                'actor_id' => $actor->id,
                'source_key' => 'manual',
            ],
        );

        return redirect()
            ->route('admin.groups.index')
            ->with('status', 'Directory synchronization requested.');
    }

    public function enable(
        Request $request,
        string $directoryGroup,
        DirectoryGroupMappingService $mappings,
    ): RedirectResponse {
        return $this->setAppEnabled($request, $directoryGroup, $mappings, true);
    }

    public function disable(
        Request $request,
        string $directoryGroup,
        DirectoryGroupMappingService $mappings,
    ): RedirectResponse {
        return $this->setAppEnabled($request, $directoryGroup, $mappings, false);
    }

    public function storeMapping(
        Request $request,
        string $directoryGroup,
        DirectoryGroupMappingService $mappings,
    ): RedirectResponse {
        Gate::authorize('admin.group-mappings.manage');
        $managedGroup = DirectoryGroup::query()->findOrFail(
            $directoryGroup,
        );
        $validated = $request->validate([
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where('guard_name', 'web'),
            ],
        ]);
        $role = Role::query()->findOrFail($validated['role_id']);

        try {
            $mappings->map($request->user(), $managedGroup, $role);
        } catch (DomainException|InvalidArgumentException|LogicException $exception) {
            return back()->withErrors([
                'authorization' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.groups.index')
            ->with('status', 'Directory mapping updated.');
    }

    public function destroyMapping(
        Request $request,
        string $mapping,
        DirectoryGroupMappingService $mappings,
    ): RedirectResponse {
        Gate::authorize('admin.group-mappings.manage');
        $managedMapping = DirectoryGroupRoleMapping::query()->findOrFail(
            $mapping,
        );

        try {
            $mappings->remove($request->user(), $managedMapping);
        } catch (DomainException|LogicException $exception) {
            return back()->withErrors([
                'authorization' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.groups.index')
            ->with('status', 'Directory mapping removed.');
    }

    private function setAppEnabled(
        Request $request,
        string $directoryGroup,
        DirectoryGroupMappingService $mappings,
        bool $enabled,
    ): RedirectResponse {
        Gate::authorize('admin.group-mappings.manage');
        $managedGroup = DirectoryGroup::query()->findOrFail(
            $directoryGroup,
        );

        try {
            $mappings->setAppEnabled(
                $request->user(),
                $managedGroup,
                $enabled,
            );
        } catch (DomainException|InvalidArgumentException|LogicException $exception) {
            return back()->withErrors([
                'authorization' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('admin.groups.index')
            ->with(
                'status',
                $enabled
                    ? 'Directory group enabled for this app.'
                    : 'Directory group disabled for this app.',
            );
    }
}
