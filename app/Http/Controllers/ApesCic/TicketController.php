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
        $canChangeAssignment = $assignments->allows($user);

        $messagesQuery = $ticket->messages()->with('user')->latest('created_at');
        if (! $user->can(AuthorizationProfile::PERMISSION_STAFF_ACCESS)) {
            $messagesQuery->where('is_staff_note', false);
        }

        return view('apes-cic.tickets.show', [
            'ticket' => $ticket->load(['user', 'assignedTo']),
            'messages' => $messagesQuery->get(),
            'canChangeAssignment' => $canChangeAssignment,
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
        $assignmentRequested = array_key_exists(
            'assigned_to',
            $request->all(),
        );
        $isStaff = $request->user()->can(
            AuthorizationProfile::PERMISSION_STAFF_ACCESS,
        );

        if ($assignmentRequested) {
            $assignments->authorizeChange(
                $request,
                $request->user(),
                $ticket,
            );
        }

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'assigned_to' => [
                'sometimes',
                'nullable',
                'integer',
                new EligibleStaffAssignee,
            ],
            'message' => ['nullable', 'string'],
        ]);

        $updates = [
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'closed_at' => in_array($validated['status'], ['resolved', 'closed'], true) ? now() : null,
        ];

        if ($assignmentRequested) {
            $updates['assigned_to'] = $validated['assigned_to'] ?? null;
        }

        $ticket->update($updates);

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
