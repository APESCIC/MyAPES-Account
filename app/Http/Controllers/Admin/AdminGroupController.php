<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\RunDirectorySync;
use App\Models\DirectoryGroup;
use App\Services\AuditLogger;
use App\Services\ManualDirectorySyncQueueResolver;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminGroupController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => [
                'nullable',
                \Illuminate\Validation\Rule::in([
                    DirectoryGroup::STATUS_PRESENT,
                    DirectoryGroup::STATUS_MISSING,
                ]),
            ],
        ]);
        $query = DirectoryGroup::query()
            ->with([
                'roles' => fn ($query) => $query->orderBy('name'),
            ])
            ->managedMyApesGroups();

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

        return view('admin.groups.index', [
            'groups' => $query
                ->orderBy('name')
                ->orderBy('id')
                ->paginate(25)
                ->withQueryString(),
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
}
