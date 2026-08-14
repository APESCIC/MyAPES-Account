@extends('layouts.app')

@section('title', 'Ticket #'.$ticket->id)

@section('content')
    <div class="panel">
        <span class="service-label {{ $ticketService->presentationClass }}">{{ $ticketService->serviceName }}</span>
        <h1>Ticket #{{ $ticket->id }} - {{ $ticket->subject }}</h1>
        <p class="muted">{{ $ticket->description }}</p>
        @if($canUpdateTicket || $canCommentTicket)
            <form id="ticket-workflow-form" method="post" action="{{ route($ticketService->routePrefix.'.update', $ticket) }}">
                @csrf
                @method('put')
                @if($canUpdateTicket)
                    <div class="row">
                        <div>
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                @foreach(['open', 'in_progress', 'resolved', 'closed'] as $status)
                                    @continue(in_array($status, ['resolved', 'closed'], true) && ! $canCloseTicket && $ticket->status !== $status)
                                    <option value="{{ $status }}" @selected($ticket->status === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="priority">Priority</label>
                            <select id="priority" name="priority">
                                @foreach(['low', 'medium', 'high', 'urgent'] as $priority)
                                    <option value="{{ $priority }}" @selected($ticket->priority === $priority)>{{ $priority }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
                @if($canCommentTicket)
                    <label for="message">Add message</label>
                    <textarea id="message" name="message"></textarea>
                    @if($canChooseVisibility)
                        <label for="visibility">Visibility</label>
                        <select id="visibility" name="visibility">
                            <option value="public">Public</option>
                            <option value="internal">Internal staff only</option>
                        </select>
                    @endif
                @endif
                <div class="actions">
                    <button type="submit">{{ $canUpdateTicket ? 'Save ticket' : 'Add message' }}</button>
                </div>
            </form>
        @endif
        @if($canChangeAssignment)
            <form id="ticket-assignment-form" method="post" action="{{ route($ticketService->routePrefix.'.update', $ticket) }}">
                @csrf
                @method('put')
                <div>
                    <label for="assigned_to">Assigned staff</label>
                    <select id="assigned_to" name="assigned_to">
                        <option value="">Unassigned</option>
                        @foreach($staffUsers as $staffUser)
                            <option value="{{ $staffUser->id }}" @selected((int)$ticket->assigned_to === (int)$staffUser->id)>{{ $staffUser->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit">Update assignment</button>
            </form>
        @endif
        <div class="actions">
            <a href="{{ route($ticketService->routePrefix.'.index') }}">Back</a>
        </div>
        @if($ticketService->supportsDelete && auth()->user()->can('delete', $ticket))
            <form method="post" action="{{ route($ticketService->routePrefix.'.destroy', $ticket) }}" onsubmit="return confirm('Delete this ticket?')">
                @csrf
                @method('delete')
                <button type="submit" class="danger-btn">Delete ticket</button>
            </form>
        @endif
    </div>
    <div class="panel">
        <h2>Activity</h2>
        @foreach(($messages ?? $ticket->messages) as $message)
            <div class="item-divider">
                <strong>{{ $message->user->name }}</strong>
                <span class="muted">{{ $message->created_at }}</span>
                @if($message->is_staff_note)
                    <span class="status">staff</span>
                @endif
                <div>{{ $message->message }}</div>
            </div>
        @endforeach
    </div>
@endsection
