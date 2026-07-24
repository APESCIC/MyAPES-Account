<?php

namespace App\Http\Controllers\ApesCic;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        $query = SupportTicket::query()->with(['user', 'assignedTo'])->latest();

        if (! $user->isStaff()) {
            $query->where('user_id', $user->id);
        }

        return view('apes-cic.tickets.index', [
            'tickets' => $query->paginate(20),
            'serviceAreas' => ['legal', 'human_resources', 'it', 'web_dev', 'operations', 'other'],
            'staffUsers' => User::query()
                ->whereIn('role', [User::ROLE_STAFF, User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service_area' => ['required', 'string', 'max:80'],
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

    public function update(Request $request, SupportTicket $ticket): RedirectResponse
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

        return redirect()->route('apes-cic.tickets.show', $ticket)->with('status', 'Ticket updated.');
    }

    public function destroy(SupportTicket $ticket): RedirectResponse
    {
        if (! request()->user()->isStaff()) {
            abort(403);
        }

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
}
