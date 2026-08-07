<?php

namespace App\Http\Controllers\ApesCic;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\TicketUpdatedNotification;
use App\Rules\EligibleStaffAssignee;
use App\Services\AssignmentAuthorization;
use App\Services\AuditLogger;
use App\Services\AuthorizationProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    private const SERVICE_AREAS = ['legal', 'human_resources', 'it', 'web_dev', 'operations', 'other'];

    private const PERMISSION_ASSIGN = 'apes-cic.tickets.assign';

    private const PERMISSION_CLOSE = 'apes-cic.tickets.close';

    private const PERMISSION_COMMENT_OWN = 'apes-cic.tickets.comment-own';

    private const PERMISSION_UPDATE_ALL = 'apes-cic.tickets.update-all';

    public function index(): View
    {
        $user = request()->user();
        Gate::authorize('viewAny', SupportTicket::class);
        $query = SupportTicket::query()
            ->visibleTo($user)
            ->with(['user', 'assignedTo'])
            ->latest();

        return view('apes-cic.tickets.index', [
            'tickets' => $query->paginate(20),
            'serviceAreas' => self::SERVICE_AREAS,
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'service_area' => ['required', Rule::in(self::SERVICE_AREAS)],
            'subject' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'description' => ['required', 'string'],
        ]);

        $ticket = SupportTicket::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'message' => 'Ticket created.',
            'is_staff_note' => false,
        ]);

        $this->notifyTicketStakeholders($ticket, $request->user(), 'created');
        $auditLogger->record('apes_cic.ticket.created', $request->user(), $ticket, [
            'service_area' => $ticket->service_area,
            'priority' => $ticket->priority,
        ]);

        return redirect()->route('apes-cic.tickets.show', $ticket);
    }

    public function show(
        SupportTicket $ticket,
        AssignmentAuthorization $assignments,
    ): View {
        Gate::authorize('view', $ticket);
        $user = request()->user();
        $canChangeAssignment = $assignments->allows($user)
            && $user->can(self::PERMISSION_ASSIGN);
        $canUpdateTicket = $user->can(self::PERMISSION_UPDATE_ALL);
        $canCloseTicket = $user->can(self::PERMISSION_CLOSE);
        $canCommentTicket = $user->can(self::PERMISSION_COMMENT_OWN);

        $messagesQuery = $ticket->messages()->with('user')->latest('created_at');
        if (! $user->can(AuthorizationProfile::PERMISSION_STAFF_ACCESS)) {
            $messagesQuery->where('is_staff_note', false);
        }

        return view('apes-cic.tickets.show', [
            'ticket' => $ticket->load(['user', 'assignedTo']),
            'messages' => $messagesQuery->get(),
            'canChangeAssignment' => $canChangeAssignment,
            'canUpdateTicket' => $canUpdateTicket,
            'canCloseTicket' => $canCloseTicket,
            'canCommentTicket' => $canCommentTicket,
            'staffUsers' => $canChangeAssignment
                ? User::query()
                    ->eligibleStaff()
                    ->orderBy('name')
                    ->get()
                : collect(),
        ]);
    }

    public function update(
        Request $request,
        SupportTicket $ticket,
        AuditLogger $auditLogger,
        AssignmentAuthorization $assignments,
    ): RedirectResponse {
        Gate::authorize('update', $ticket);
        $input = $request->all();
        $assignmentRequested = array_key_exists(
            'assigned_to',
            $input,
        );
        $ticketUpdateRequested = array_key_exists('status', $input)
            || array_key_exists('priority', $input);
        $messageRequested = array_key_exists('message', $input);
        $isStaff = $request->user()->can(
            AuthorizationProfile::PERMISSION_STAFF_ACCESS,
        );

        if ($assignmentRequested) {
            $assignments->authorizeChange(
                $request,
                $request->user(),
                $ticket,
            );
            Gate::authorize(self::PERMISSION_ASSIGN);
        }

        if ($ticketUpdateRequested) {
            Gate::authorize(self::PERMISSION_UPDATE_ALL);

            $requestedStatus = $request->input('status');
            if (is_string($requestedStatus)
                && in_array($requestedStatus, ['resolved', 'closed'], true)
                && $requestedStatus !== $ticket->status) {
                Gate::authorize(self::PERMISSION_CLOSE);
            }
        }

        if ($messageRequested) {
            Gate::authorize(self::PERMISSION_COMMENT_OWN);
        }

        $rules = [
            'message' => [
                $ticketUpdateRequested || $assignmentRequested
                    ? 'nullable'
                    : 'required',
                'string',
            ],
        ];
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
                new EligibleStaffAssignee,
            ];
        }
        $validated = $request->validate($rules);

        $updates = [];
        if ($ticketUpdateRequested) {
            $updates = [
                'status' => $validated['status'],
                'priority' => $validated['priority'],
                'closed_at' => in_array(
                    $validated['status'],
                    ['resolved', 'closed'],
                    true,
                ) ? now() : null,
            ];
        }

        if ($assignmentRequested) {
            $updates['assigned_to'] = $validated['assigned_to'] ?? null;
        }

        if ($updates !== []) {
            $ticket->update($updates);
        }

        if (! empty($validated['message'])) {
            $ticket->messages()->create([
                'user_id' => $request->user()->id,
                'message' => $validated['message'],
                'is_staff_note' => $isStaff,
            ]);
        }

        $this->notifyTicketStakeholders($ticket, $request->user(), 'updated');
        $auditLogger->record('apes_cic.ticket.updated', $request->user(), $ticket, [
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'assigned_to' => $ticket->assigned_to,
        ]);

        return redirect()->route('apes-cic.tickets.show', $ticket)->with('status', 'Ticket updated.');
    }

    public function destroy(SupportTicket $ticket, AuditLogger $auditLogger): RedirectResponse
    {
        $actor = request()->user();
        Gate::authorize('delete', $ticket);

        $auditLogger->record('apes_cic.ticket.deleted', $actor, $ticket, [
            'subject' => $ticket->subject,
        ]);
        $ticket->delete();

        return redirect()->route('apes-cic.tickets.index')->with('status', 'Ticket deleted.');
    }

    private function notifyTicketStakeholders(SupportTicket $ticket, User $actor, string $eventLabel): void
    {
        $staffRecipients = User::query()
            ->eligibleStaff()
            ->get();

        $ticketRecipients = $staffRecipients
            ->push($ticket->user)
            ->unique('id')
            ->reject(fn (User $recipient): bool => $recipient->id === $actor->id);

        foreach ($ticketRecipients as $recipient) {
            $recipient->notify(new TicketUpdatedNotification($ticket, $actor, $eventLabel));
        }
    }
}
