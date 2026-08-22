<?php

namespace App\Http\Controllers\ApesCic;

use App\Http\Controllers\Controller;
use App\Models\ShelterCase;
use App\Models\User;
use App\Modules\ModuleInstanceDefinition;
use App\Notifications\ApesCicCaseUpdatedNotification;
use App\Rules\EligibleStaffAssignee;
use App\Rules\EligibleTicketOwner;
use App\Services\AssignmentAuthorization;
use App\Services\AuditLogger;
use App\Services\CaseCategoryResolver;
use App\Services\ModuleRouteContext;
use App\Services\SecureUploadService;
use App\Services\SupportAttachmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CaseController extends Controller
{
    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public const STATUSES = ['open', 'in_progress', 'waiting_on_user', 'resolved', 'closed'];

    public function __construct(
        private readonly ModuleRouteContext $moduleContext,
        private readonly CaseCategoryResolver $categories,
        private readonly SupportAttachmentService $attachments,
    ) {}

    public function index(Request $request): View
    {
        $instance = $this->instance($request);
        $prefix = $this->moduleContext->permissionPrefix($instance);
        $user = $request->user();
        abort_unless(
            $user->can($prefix.'view-own') || $user->can($prefix.'view-all'),
            403,
        );

        return view('apes-cic.cases.index', [
            'cases' => ShelterCase::query()
                ->forSubCore($instance->subCore->key)
                ->visibleTo($user, $instance->subCore->key)
                ->with(['user', 'assignedTo'])
                ->latest()
                ->paginate(20),
            'categoryGroups' => $this->categories->categories($instance->subCore->key),
            'websites' => $this->categories->websites($instance->subCore->key),
            'priorities' => self::PRIORITIES,
            'canCreateCase' => $user->can($prefix.'create'),
            'categoryResolver' => $this->categories,
        ]);
    }

    public function store(
        Request $request,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $instance = $this->instance($request);
        $prefix = $this->moduleContext->permissionPrefix($instance);
        Gate::authorize($prefix.'create');
        $validated = $request->validate([
            'category' => ['required', Rule::in($this->categories->categoryKeys($instance->subCore->key))],
            'sub_category' => ['required', 'string', 'max:64'],
            'affected_website_key' => ['nullable', 'string', 'max:64'],
            'priority' => ['required', Rule::in(self::PRIORITIES)],
            'title' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string'],
            'screenshots' => ['nullable', 'array', 'max:5'],
            'screenshots.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'screencast' => [
                'nullable',
                'file',
                'mimetypes:video/mp4,video/webm',
                'max:'.SecureUploadService::SCREENCAST_MAX_KB,
            ],
        ]);

        $categoryFields = $this->categories->validateSelection(
            $instance->subCore->key,
            $validated['category'],
            $validated['sub_category'],
            $validated['affected_website_key'] ?? null,
        );

        $allowsAttachments = $this->categories->allowsAttachments(
            $instance->subCore->key,
            $categoryFields['category'],
            $categoryFields['sub_category'],
        );
        if (! $allowsAttachments && (
            $request->hasFile('screenshots') || $request->hasFile('screencast')
        )) {
            throw ValidationException::withMessages([
                'screenshots' => 'Attachments are not available for this subcategory.',
            ]);
        }

        $case = ShelterCase::create([
            'category' => $categoryFields['category'],
            'sub_category' => $categoryFields['sub_category'],
            'affected_website_key' => $categoryFields['affected_website_key'],
            'priority' => $validated['priority'],
            'title' => $validated['title'],
            'details' => $validated['details'] ?? null,
            'sub_core_key' => $instance->subCore->key,
            'user_id' => $request->user()->id,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        if ($allowsAttachments) {
            $this->attachments->storeFor(
                $case,
                $instance->subCore->key,
                $request->user(),
                $request->file('screenshots'),
                $request->file('screencast'),
            );
        }

        $this->notifyStakeholders($case, $request->user(), 'created', $instance);
        $auditLogger->record('apes_cic.case.created', $request->user(), $case, [
            'sub_core_key' => $case->sub_core_key,
            'module_key' => $instance->module->key,
            'category' => $case->category,
            'sub_category' => $case->sub_category,
            'affected_website_key' => $case->affected_website_key,
            'priority' => $case->priority,
            'status' => $case->status,
        ]);

        return redirect()->route($this->moduleContext->showRouteName($instance), $case);
    }

    public function show(
        Request $request,
        ShelterCase $case,
        AssignmentAuthorization $assignments,
    ): View {
        $instance = $this->instance($request);
        $prefix = $this->moduleContext->permissionPrefix($instance);
        $this->requireCaseForInstance($case, $instance);
        Gate::authorize('view', $case);
        $user = $request->user();
        $canViewAll = $user->can($prefix.'view-all');
        $canChangeAssignment = $assignments->allows($user)
            && $user->can($prefix.'assign');
        $allowsAttachments = is_string($case->sub_category)
            && $this->categories->allowsAttachments(
                $instance->subCore->key,
                (string) $case->category,
                $case->sub_category,
            );

        $updates = $case->updates()->with('user')->oldest();
        if (! $canViewAll) {
            $updates->where('visibility', 'public');
        }

        return view('apes-cic.cases.show', [
            'case' => $case->load(['user', 'assignedTo', 'attachments']),
            'updates' => $updates->get(),
            'categoryGroups' => $this->categories->categories($instance->subCore->key),
            'websites' => $this->categories->websites($instance->subCore->key),
            'priorities' => self::PRIORITIES,
            'statuses' => self::STATUSES,
            'canUpdateCase' => $user->can($prefix.'update-all'),
            'canCloseCase' => $user->can($prefix.'close'),
            'canCommentCase' => $user->can($prefix.'comment-own')
                && $case->status !== 'closed',
            'canChooseVisibility' => $canViewAll,
            'canChangeAssignment' => $canChangeAssignment,
            'allowsAttachments' => $allowsAttachments,
            'categoryResolver' => $this->categories,
            'staffUsers' => $canChangeAssignment
                ? User::query()
                    ->eligibleStaff()
                    ->withAuthorizationPermission($prefix.'view-all')
                    ->orderBy('name')
                    ->get()
                : collect(),
            'ownerCandidates' => $canChangeAssignment
                ? User::query()->orderBy('name')->limit(200)->get()
                : collect(),
        ]);
    }

    public function update(
        Request $request,
        ShelterCase $case,
        AuditLogger $auditLogger,
        AssignmentAuthorization $assignments,
    ): RedirectResponse {
        $instance = $this->instance($request);
        $prefix = $this->moduleContext->permissionPrefix($instance);
        $this->requireCaseForInstance($case, $instance);
        Gate::authorize('view', $case);
        $assignmentRequested = $request->exists('assigned_to');
        $ownerRequested = $request->exists('user_id');
        $metadataRequested = $request->exists('category')
            || $request->exists('priority')
            || $request->exists('status')
            || $request->exists('sub_category');
        $attachmentRequested = $request->hasFile('screenshots')
            || $request->hasFile('screencast');

        if (! $metadataRequested && ! $assignmentRequested && ! $ownerRequested && ! $attachmentRequested) {
            throw ValidationException::withMessages([
                'case' => 'Select a case change before submitting.',
            ]);
        }

        if ($metadataRequested) {
            Gate::authorize($prefix.'update-all');
        }
        if ($assignmentRequested || $ownerRequested) {
            $assignments->authorizeChange($request, $request->user(), $case);
            Gate::authorize($prefix.'assign');
        }
        if ($attachmentRequested) {
            Gate::authorize($prefix.'comment-own');
            abort_unless(
                is_string($case->sub_category)
                && $this->categories->allowsAttachments(
                    $instance->subCore->key,
                    (string) $case->category,
                    $case->sub_category,
                ),
                403,
            );
        }

        $rules = [];
        if ($metadataRequested) {
            $rules = [
                'category' => ['required', Rule::in($this->categories->categoryKeys($instance->subCore->key))],
                'sub_category' => ['required', 'string', 'max:64'],
                'affected_website_key' => ['nullable', 'string', 'max:64'],
                'priority' => ['required', Rule::in(self::PRIORITIES)],
                'status' => ['required', Rule::in(self::STATUSES)],
            ];
        }
        if ($assignmentRequested) {
            $rules['assigned_to'] = [
                'sometimes',
                'nullable',
                'integer',
                new EligibleStaffAssignee($prefix.'view-all'),
            ];
        }
        if ($ownerRequested) {
            $rules['user_id'] = [
                'required',
                'integer',
                new EligibleTicketOwner,
            ];
        }
        if ($attachmentRequested) {
            $rules['screenshots'] = ['nullable', 'array', 'max:5'];
            $rules['screenshots.*'] = ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072'];
            $rules['screencast'] = [
                'nullable',
                'file',
                'mimetypes:video/mp4,video/webm',
                'max:'.SecureUploadService::SCREENCAST_MAX_KB,
            ];
        }
        $validated = $request->validate($rules);

        $updates = [];
        if ($metadataRequested) {
            $categoryFields = $this->categories->validateSelection(
                $instance->subCore->key,
                $validated['category'],
                $validated['sub_category'],
                $validated['affected_website_key'] ?? null,
            );
            $requestedStatus = $validated['status'];
            $statusChanged = $requestedStatus !== $case->status;
            $wasTerminal = in_array($case->status, ['resolved', 'closed'], true);
            $willBeTerminal = in_array($requestedStatus, ['resolved', 'closed'], true);
            if ($statusChanged && ($wasTerminal || $willBeTerminal)) {
                Gate::authorize($prefix.'close');
            }

            $updates = [
                'category' => $categoryFields['category'],
                'sub_category' => $categoryFields['sub_category'],
                'affected_website_key' => $categoryFields['affected_website_key'],
                'priority' => $validated['priority'],
                'status' => $requestedStatus,
            ];
            if ($statusChanged) {
                $updates['resolved_at'] = match ($requestedStatus) {
                    'resolved' => $case->resolved_at ?? $case->closed_at ?? now(),
                    'closed' => $case->status === 'resolved'
                        ? $case->resolved_at
                        : null,
                    default => null,
                };
                $updates['closed_at'] = $requestedStatus === 'closed' ? now() : null;
            }
        }
        if ($assignmentRequested) {
            $updates['assigned_to'] = $validated['assigned_to'] ?? null;
        }
        if ($ownerRequested) {
            $updates['user_id'] = (int) $validated['user_id'];
        }

        $storedAttachments = collect();
        if ($attachmentRequested) {
            $storedAttachments = $this->attachments->storeFor(
                $case,
                $instance->subCore->key,
                $request->user(),
                $request->file('screenshots'),
                $request->file('screencast'),
            );
        }

        if ($updates !== []) {
            $case->fill($updates);
            if (! $case->isDirty() && $storedAttachments->isEmpty()) {
                throw ValidationException::withMessages([
                    'case' => 'Select a case change before submitting.',
                ]);
            }
            if ($case->isDirty()) {
                $case->save();
            }
        } elseif ($storedAttachments->isEmpty()) {
            throw ValidationException::withMessages([
                'case' => 'Select a case change before submitting.',
            ]);
        }

        $this->notifyStakeholders($case, $request->user(), 'updated', $instance);
        $auditLogger->record('apes_cic.case.updated', $request->user(), $case, [
            'sub_core_key' => $case->sub_core_key,
            'module_key' => $instance->module->key,
            'category' => $case->category,
            'sub_category' => $case->sub_category,
            'priority' => $case->priority,
            'status' => $case->status,
            'assigned_to' => $case->assigned_to,
            'user_id' => $case->user_id,
            'attachments_added' => $storedAttachments->count(),
        ]);

        return redirect()->route($this->moduleContext->showRouteName($instance), $case)
            ->with('status', 'Case updated.');
    }

    public function destroy(
        Request $request,
        ShelterCase $case,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $instance = $this->instance($request);
        $this->requireCaseForInstance($case, $instance);
        Gate::authorize('delete', $case);
        $actor = request()->user();
        $auditLogger->record('apes_cic.case.deleted', $actor, $case, [
            'sub_core_key' => $case->sub_core_key,
            'module_key' => $instance->module->key,
            'category' => $case->category,
            'priority' => $case->priority,
            'status' => $case->status,
        ]);
        $case->attachments()->each(fn ($attachment) => $attachment->delete());
        $case->delete();

        return redirect()->route($this->moduleContext->indexRouteName($instance))
            ->with('status', 'Case deleted.');
    }

    private function requireCaseForInstance(
        ShelterCase $case,
        ModuleInstanceDefinition $instance,
    ): void {
        abort_unless(
            $case->sub_core_key === $instance->subCore->key,
            404,
        );
    }

    private function notifyStakeholders(
        ShelterCase $case,
        User $actor,
        string $eventLabel,
        ModuleInstanceDefinition $instance,
    ): void {
        $prefix = $this->moduleContext->permissionPrefix($instance);
        $recipients = User::query()
            ->eligibleStaff()
            ->withAuthorizationPermission($prefix.'view-all')
            ->get();
        if ($case->user?->can('view', $case)) {
            $recipients->push($case->user);
        }

        $recipients
            ->unique('id')
            ->reject(fn (User $recipient): bool => $recipient->id === $actor->id)
            ->each(fn (User $recipient) => $recipient->notify(
                new ApesCicCaseUpdatedNotification(
                    $case,
                    $actor,
                    $eventLabel,
                    $instance->subCore->key,
                    $this->moduleContext->showRouteName($instance),
                ),
            ));
    }

    private function instance(Request $request): ModuleInstanceDefinition
    {
        return $this->moduleContext->resolve($request, 'cases');
    }
}
