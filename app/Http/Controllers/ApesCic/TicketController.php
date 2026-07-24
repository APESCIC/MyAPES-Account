<?php

namespace App\Http\Controllers\ApesCic;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\TicketUpdatedNotification;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    private const SERVICE_AREAS = ['legal', 'human_resources', 'it', 'web_dev', 'operations', 'other'];

    public function index(): View
    {
        $user = request()->user();
        $query = SupportTicket::query()->with(['user', 'assignedTo'])->latest();

        if (! $user->isStaff()) {
            $query->where('user_id', $user->id);
        }

        return view('apes-cic.tickets.index', [
            'tickets' => $query->paginate(20),
            'serviceAreas' => self::SERVICE_AREAS,
            'staffUsers' => User::query()
                ->whereIn('role', [User::ROLE_STAFF, User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
                ->orderBy('name')
                ->get(),
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

    public function show(SupportTicket $ticket): View
    {
        $this->authorizeTicketAccess($ticket);

        return view('apes-cic.tickets.show', [
            'ticket' => $ticket->load(['user', 'assignedTo', 'messages.user']),
            'staffUsers' => User::query()
                ->whereIn('role', [User::ROLE_STAFF, User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, SupportTicket $ticket, AuditLogger $auditLogger): RedirectResponse
    {
        $this->authorizeTicketAccess($ticket);

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'message' => ['nullable', 'string'],
        ]);

        if (! $request->user()->isStaff()) {
            $validated['assigned_to'] = null;
        }

        $ticket->update([
            'status' => $validated['status'],
            'priority' => $validated['priority'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'closed_at' => in_array($validated['status'], ['resolved', 'closed'], true) ? now() : null,
        ]);

        if (! empty($validated['message'])) {
            $ticket->messages()->create([
                'user_id' => $request->user()->id,
                'message' => $validated['message'],
                'is_staff_note' => $request->user()->isStaff(),
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
        if (! $actor->isStaff()) {
            abort(403);
        }

        $auditLogger->record('apes_cic.ticket.deleted', $actor, $ticket, [
            'subject' => $ticket->subject,
        ]);
        $ticket->delete();

        return redirect()->route('apes-cic.tickets.index')->with('status', 'Ticket deleted.');
    }

    private function authorizeTicketAccess(SupportTicket $ticket): void
    {
        $user = request()->user();

        if ($user->isStaff()) {
            return;
        }

        if ($ticket->user_id !== $user->id) {
            abort(403);
        }
    }

    private function notifyTicketStakeholders(SupportTicket $ticket, User $actor, string $eventLabel): void
    {
        $staffRecipients = User::query()
            ->whereIn('role', [User::ROLE_STAFF, User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
            ->get();

        $ticketRecipients = $staffRecipients
            ->push($ticket->user)
            ->when($ticket->assignedTo !== null, fn ($collection) => $collection->push($ticket->assignedTo))
            ->unique('id')
            ->reject(fn (User $recipient): bool => $recipient->id === $actor->id);

        foreach ($ticketRecipients as $recipient) {
            $recipient->notify(new TicketUpdatedNotification($ticket, $actor, $eventLabel));
        }
    }
}
