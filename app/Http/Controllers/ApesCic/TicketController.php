<?php

namespace App\Http\Controllers\ApesCic;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use App\Modules\ModuleInstanceDefinition;
use App\Notifications\TicketUpdatedNotification;
use App\Rules\EligibleStaffAssignee;
use App\Services\AssignmentAuthorization;
use App\Services\AuditLogger;
use App\Services\ModuleRouteContext;
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

        return view('apes-cic.tickets.index', [
            'tickets' => $query->paginate(20),
            'serviceAreas' => $ticketService->serviceAreas,
            'canCreateTicket' => $user->can($prefix.'create'),
            'ticketService' => $ticketService,
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $instance = $this->instance($request);
        $prefix = $this->moduleContext->permissionPrefix($instance);
        $ticketService = $this->ticketServices->for($instance->subCore->key);
        Gate::authorize($prefix.'create');
        $validated = $request->validate([
            'service_area' => ['required', Rule::in($ticketService->serviceAreas)],
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'description' => ['required', 'string'],
        ]);

        $ticket = SupportTicket::create([
            ...$validated,
            'sub_core_key' => $instance->subCore->key,
            'user_id' => $request->user()->id,
            'status' => 'open',
        ]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'message' => 'Ticket created.',
            'is_staff_note' => false,
        ]);

        $this->notifyTicketStakeholders($ticket, $request->user(), 'created', $instance);
        $auditLogger->record("{$ticketService->auditPrefix}.created", $request->user(), $ticket, [
            'service_area' => $ticket->service_area,
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

        $messagesQuery = $ticket->messages()->with('user')->latest('created_at');
        if (! $user->can($prefix.'view-all')) {
            $messagesQuery->where('is_staff_note', false);
        }

        return view('apes-cic.tickets.show', [
            'ticket' => $ticket->load(['user', 'assignedTo']),
            'messages' => $messagesQuery->get(),
            'canChangeAssignment' => $canChangeAssignment,
            'canUpdateTicket' => $canUpdateTicket,
            'canCloseTicket' => $canCloseTicket,
            'canCommentTicket' => $canCommentTicket,
            'canChooseVisibility' => $canChooseVisibility,
            'staffUsers' => $canChangeAssignment
                ? User::query()
                    ->eligibleStaff()
                    ->withAuthorizationPermission($prefix.'view-all')
                    ->orderBy('name')
                    ->get()
                : collect(),
            'ticketService' => $ticketService,
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
        $assignmentRequested = array_key_exists(
            'assigned_to',
            $input,
        );
        $ticketUpdateRequested = array_key_exists('status', $input)
            || array_key_exists('priority', $input);
        $messageRequested = array_key_exists('message', $input);
        $canChooseVisibility = $request->user()->can(
            $prefix.'view-all',
        );

        if ($assignmentRequested) {
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

        $rules = [
            'message' => [
                $ticketUpdateRequested || $assignmentRequested
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

        $message = $validated['message'] ?? null;
        $hasMessage = is_string($message) && $message !== '';
        $ticketChanged = $updates !== [];
        if (! $hasMessage && ! $ticketChanged) {
            throw ValidationException::withMessages([
                'ticket' => 'Select a ticket change or add a message before submitting.',
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
        if ($hasMessage && ! $internalMessage && ! $ticketChanged) {
            $ticket->touch();
        }
        $this->notifyTicketStakeholders(
            $ticket,
            $request->user(),
            'updated',
            $instance,
            $internalMessage && ! $ticketChanged,
        );
        $auditLogger->record("{$ticketService->auditPrefix}.updated", $request->user(), $ticket, [
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'assigned_to' => $ticket->assigned_to,
            'sub_core_key' => $ticket->sub_core_key,
            'module_key' => $instance->module->key,
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
