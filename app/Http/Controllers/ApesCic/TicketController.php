<?php

namespace App\Http\Controllers\ApesCic;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use App\Modules\ModuleInstanceDefinition;
use App\Notifications\TicketUpdatedNotification;
use App\Rules\EligibleStaffAssignee;
use App\Rules\EligibleTicketOwner;
use App\Services\AssignmentAuthorization;
use App\Services\AuditLogger;
use App\Services\ModuleRouteContext;
use App\Services\SecureUploadService;
use App\Services\SupportAttachmentService;
use App\Services\TicketCategoryResolver;
use App\Services\TicketServiceConfiguration;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    public function __construct(
        private readonly ModuleRouteContext $moduleContext,
        private readonly TicketServiceConfiguration $ticketServices,
        private readonly TicketCategoryResolver $categories,
        private readonly SupportAttachmentService $attachments,
    ) {}

    public function index(Request $request): View
    {
        $instance = $this->instance($request);
        $prefix = $this->moduleContext->permissionPrefix($instance);
        $ticketService = $this->ticketServices->for($instance->subCore->key);
        $user = $request->user();
        abort_unless(
            $user->can($prefix.'view-own') || $user->can($prefix.'view-all'),
            403,
        );
        $query = SupportTicket::query()
            ->forSubCore($instance->subCore->key)
            ->visibleTo($user, $instance->subCore->key)
            ->with(['user', 'assignedTo'])
            ->latest();

        $isApesCic = $instance->subCore->key === 'apes-cic';

        return view('apes-cic.tickets.index', [
            'tickets' => $query->paginate(20),
            'serviceAreas' => $ticketService->serviceAreas,
            'serviceAreaGroups' => $isApesCic
                ? $this->categories->serviceAreas($instance->subCore->key)
                : [],
            'websites' => $isApesCic
                ? $this->categories->websites($instance->subCore->key)
                : [],
            'usesHierarchicalCategories' => $isApesCic,
            'canCreateTicket' => $user->can($prefix.'create'),
            'ticketService' => $ticketService,
            'categoryResolver' => $this->categories,
        ]);
    }

    public function store(
        Request $request,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $instance = $this->instance($request);
        $prefix = $this->moduleContext->permissionPrefix($instance);
        $ticketService = $this->ticketServices->for($instance->subCore->key);
        Gate::authorize($prefix.'create');

        $isApesCic = $instance->subCore->key === 'apes-cic';
        $rules = [
            'service_area' => ['required', Rule::in($ticketService->serviceAreas)],
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'description' => ['required', 'string'],
        ];

        if ($isApesCic) {
            $rules['sub_category'] = ['required', 'string', 'max:64'];
            $rules['affected_website_key'] = ['nullable', 'string', 'max:64'];
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

        $categoryFields = [
            'service_area' => $validated['service_area'],
            'sub_category' => null,
            'affected_website_key' => null,
        ];

        if ($isApesCic) {
            $categoryFields = $this->categories->validateSelection(
                $instance->subCore->key,
                $validated['service_area'],
                $validated['sub_category'],
                $validated['affected_website_key'] ?? null,
            );
            $allowsAttachments = $this->categories->allowsAttachments(
                $instance->subCore->key,
                $categoryFields['service_area'],
                $categoryFields['sub_category'],
            );
            if (! $allowsAttachments && (
                $request->hasFile('screenshots') || $request->hasFile('screencast')
            )) {
                throw ValidationException::withMessages([
                    'screenshots' => 'Attachments are not available for this subcategory.',
                ]);
            }
        }

        $ticket = SupportTicket::create([
            'service_area' => $categoryFields['service_area'],
            'sub_category' => $categoryFields['sub_category'],
            'affected_website_key' => $categoryFields['affected_website_key'],
            'subject' => $validated['subject'],
            'priority' => $validated['priority'],
            'description' => $validated['description'],
            'sub_core_key' => $instance->subCore->key,
            'user_id' => $request->user()->id,
            'status' => 'open',
        ]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'message' => 'Ticket created.',
            'is_staff_note' => false,
        ]);

        if ($isApesCic && $this->categories->allowsAttachments(
            $instance->subCore->key,
            $categoryFields['service_area'],
            (string) $categoryFields['sub_category'],
        )) {
            $this->attachments->storeFor(
                $ticket,
                $instance->subCore->key,
                $request->user(),
                $request->file('screenshots'),
                $request->file('screencast'),
            );
        }

        $this->notifyTicketStakeholders($ticket, $request->user(), 'created', $instance);
        $auditLogger->record("{$ticketService->auditPrefix}.created", $request->user(), $ticket, [
            'service_area' => $ticket->service_area,
            'sub_category' => $ticket->sub_category,
            'affected_website_key' => $ticket->affected_website_key,
            'priority' => $ticket->priority,
            'sub_core_key' => $ticket->sub_core_key,
            'module_key' => $instance->module->key,
        ]);

        return redirect()->route($this->moduleContext->showRouteName($instance), $ticket);
    }

    public function show(
        Request $request,
        SupportTicket $ticket,
        AssignmentAuthorization $assignments,
    ): View {
        $instance = $this->instance($request);
        $prefix = $this->moduleContext->permissionPrefix($instance);
        $ticketService = $this->ticketServices->for($instance->subCore->key);
        $this->requireTicketForInstance($ticket, $instance);
        Gate::authorize('view', $ticket);
        $user = $request->user();
        $canChangeAssignment = $assignments->allows($user)
            && $user->can($prefix.'assign');
        $canUpdateTicket = $user->can($prefix.'update-all');
        $canCloseTicket = $user->can($prefix.'close');
        $canCommentTicket = $user->can($prefix.'comment-own');
        $canChooseVisibility = $user->can($prefix.'view-all');
        $isApesCic = $instance->subCore->key === 'apes-cic';
        $allowsAttachments = $isApesCic
            && is_string($ticket->sub_category)
            && $this->categories->allowsAttachments(
                $instance->subCore->key,
                $ticket->service_area,
                $ticket->sub_category,
            );

        $messagesQuery = $ticket->messages()->with('user')->latest('created_at');
        if (! $user->can($prefix.'view-all')) {
            $messagesQuery->where('is_staff_note', false);
        }

        return view('apes-cic.tickets.show', [
            'ticket' => $ticket->load(['user', 'assignedTo', 'attachments']),
            'messages' => $messagesQuery->get(),
            'canChangeAssignment' => $canChangeAssignment,
            'canUpdateTicket' => $canUpdateTicket,
            'canCloseTicket' => $canCloseTicket,
            'canCommentTicket' => $canCommentTicket,
            'canChooseVisibility' => $canChooseVisibility,
            'allowsAttachments' => $allowsAttachments,
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
            'ticketService' => $ticketService,
            'usesHierarchicalCategories' => $isApesCic,
            'categoryResolver' => $this->categories,
            'serviceAreaGroups' => $isApesCic
                ? $this->categories->serviceAreas($instance->subCore->key)
                : [],
            'websites' => $isApesCic
                ? $this->categories->websites($instance->subCore->key)
                : [],
        ]);
    }

    public function update(
        Request $request,
        SupportTicket $ticket,
        AuditLogger $auditLogger,
        AssignmentAuthorization $assignments,
    ): RedirectResponse {
        $instance = $this->instance($request);
        $prefix = $this->moduleContext->permissionPrefix($instance);
        $ticketService = $this->ticketServices->for($instance->subCore->key);
        $this->requireTicketForInstance($ticket, $instance);
        Gate::authorize('update', $ticket);
        $input = $request->all();
        $assignmentRequested = array_key_exists('assigned_to', $input);
        $ownerRequested = array_key_exists('user_id', $input);
        $ticketUpdateRequested = array_key_exists('status', $input)
            || array_key_exists('priority', $input);
        $messageRequested = array_key_exists('message', $input);
        $attachmentRequested = $request->hasFile('screenshots')
            || $request->hasFile('screencast');
        $canChooseVisibility = $request->user()->can(
            $prefix.'view-all',
        );
        $isApesCic = $instance->subCore->key === 'apes-cic';

        if ($assignmentRequested || $ownerRequested) {
            $assignments->authorizeChange(
                $request,
                $request->user(),
                $ticket,
            );
            Gate::authorize($prefix.'assign');
        }

        if ($ticketUpdateRequested) {
            Gate::authorize($prefix.'update-all');
        }

        if ($messageRequested) {
            Gate::authorize($prefix.'comment-own');
        }

        if ($attachmentRequested) {
            Gate::authorize($prefix.'comment-own');
            abort_unless(
                $isApesCic
                && is_string($ticket->sub_category)
                && $this->categories->allowsAttachments(
                    $instance->subCore->key,
                    $ticket->service_area,
                    $ticket->sub_category,
                ),
                403,
            );
        }

        $rules = [
            'message' => [
                $ticketUpdateRequested || $assignmentRequested || $ownerRequested || $attachmentRequested
                    ? 'nullable'
                    : 'required',
                'string',
            ],
        ];
        if ($messageRequested) {
            $rules['visibility'] = ['nullable', 'in:public,internal'];
        }
        if ($ticketUpdateRequested) {
            $rules['status'] = [
                'required',
                'in:open,in_progress,resolved,closed',
            ];
            $rules['priority'] = [
                'required',
                'in:low,medium,high,urgent',
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
        if ($ticketUpdateRequested) {
            $requestedStatus = $validated['status'];
            $statusChanged = $requestedStatus !== $ticket->status;
            $wasTerminal = in_array($ticket->status, ['resolved', 'closed'], true);
            $willBeTerminal = in_array($requestedStatus, ['resolved', 'closed'], true);
            if ($statusChanged && ($wasTerminal || $willBeTerminal)) {
                Gate::authorize($prefix.'close');
            }
            if ($statusChanged) {
                $updates['status'] = $requestedStatus;
                $updates['closed_at'] = match (true) {
                    ! $wasTerminal && $willBeTerminal => now(),
                    $wasTerminal && $willBeTerminal => $ticket->closed_at,
                    default => null,
                };
            }
            if ($validated['priority'] !== $ticket->priority) {
                $updates['priority'] = $validated['priority'];
            }
        }

        if ($assignmentRequested) {
            $assignedTo = isset($validated['assigned_to'])
                ? (int) $validated['assigned_to']
                : null;
            $currentAssignee = $ticket->assigned_to === null
                ? null
                : (int) $ticket->assigned_to;
            if ($assignedTo !== $currentAssignee) {
                $updates['assigned_to'] = $assignedTo;
            }
        }

        if ($ownerRequested) {
            $ownerId = (int) $validated['user_id'];
            if ($ownerId !== (int) $ticket->user_id) {
                $updates['user_id'] = $ownerId;
            }
        }

        $message = $validated['message'] ?? null;
        $hasMessage = is_string($message) && $message !== '';
        $ticketChanged = $updates !== [];
        $storedAttachments = collect();
        if ($attachmentRequested) {
            $storedAttachments = $this->attachments->storeFor(
                $ticket,
                $instance->subCore->key,
                $request->user(),
                $request->file('screenshots'),
                $request->file('screencast'),
            );
        }
        $hasAttachments = $storedAttachments->isNotEmpty();

        if (! $hasMessage && ! $ticketChanged && ! $hasAttachments) {
            throw ValidationException::withMessages([
                'ticket' => 'Select a ticket change, add a message, or attach a file before submitting.',
            ]);
        }

        if ($ticketChanged) {
            $ticket->update($updates);
        }

        if ($hasMessage) {
            $ticket->messages()->create([
                'user_id' => $request->user()->id,
                'message' => $message,
                'is_staff_note' => $canChooseVisibility
                    && ($validated['visibility'] ?? 'public') === 'internal',
            ]);
        }

        $internalMessage = $hasMessage
            && $canChooseVisibility
            && ($validated['visibility'] ?? 'public') === 'internal';
        if (($hasMessage && ! $internalMessage && ! $ticketChanged) || $hasAttachments) {
            $ticket->touch();
        }
        $this->notifyTicketStakeholders(
            $ticket,
            $request->user(),
            'updated',
            $instance,
            $internalMessage && ! $ticketChanged && ! $hasAttachments,
        );
        $auditLogger->record("{$ticketService->auditPrefix}.updated", $request->user(), $ticket, [
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'assigned_to' => $ticket->assigned_to,
            'user_id' => $ticket->user_id,
            'sub_core_key' => $ticket->sub_core_key,
            'module_key' => $instance->module->key,
            'attachments_added' => $storedAttachments->count(),
        ]);

        return redirect()->route($this->moduleContext->showRouteName($instance), $ticket)
            ->with('status', 'Ticket updated.');
    }

    public function destroy(
        Request $request,
        SupportTicket $ticket,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $instance = $this->instance($request);
        $ticketService = $this->ticketServices->for($instance->subCore->key);
        $this->requireTicketForInstance($ticket, $instance);
        abort_unless($ticketService->supportsDelete, 404);
        $actor = $request->user();
        Gate::authorize('delete', $ticket);

        $auditLogger->record("{$ticketService->auditPrefix}.deleted", $actor, $ticket, [
            'sub_core_key' => $ticket->sub_core_key,
            'module_key' => $instance->module->key,
        ]);
        $ticket->attachments()->each(fn ($attachment) => $attachment->delete());
        $ticket->delete();

        return redirect()->route($this->moduleContext->indexRouteName($instance))
            ->with('status', 'Ticket deleted.');
    }

    private function notifyTicketStakeholders(
        SupportTicket $ticket,
        User $actor,
        string $eventLabel,
        ModuleInstanceDefinition $instance,
        bool $staffOnly = false,
    ): void {
        $prefix = $this->moduleContext->permissionPrefix($instance);
        $ticketService = $this->ticketServices->for($instance->subCore->key);
        $staffRecipients = User::query()
            ->eligibleStaff()
            ->withAuthorizationPermission($prefix.'view-all')
            ->get();

        if (! $staffOnly && $ticket->user?->can('view', $ticket)) {
            $staffRecipients->push($ticket->user);
        }

        $ticketRecipients = $staffRecipients
            ->unique('id')
            ->reject(fn (User $recipient): bool => $recipient->id === $actor->id);

        foreach ($ticketRecipients as $recipient) {
            $recipient->notify(new TicketUpdatedNotification(
                $ticket,
                $actor,
                $eventLabel,
                $instance->subCore->key,
                $this->moduleContext->showRouteName($instance),
                $ticketService->serviceName,
            ));
        }
    }

    private function requireTicketForInstance(
        SupportTicket $ticket,
        ModuleInstanceDefinition $instance,
    ): void {
        abort_unless(
            $ticket->sub_core_key === $instance->subCore->key,
            404,
        );
    }

    private function instance(Request $request): ModuleInstanceDefinition
    {
        return $this->moduleContext->resolve($request, 'tickets');
    }
}
