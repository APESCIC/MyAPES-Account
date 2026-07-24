@extends('layouts.app')

@section('title', 'Ticket #'.$ticket->id)

@section('content')
    <div class="panel">
        <span class="service-label apes-cic">APES CIC</span>
        <h1>Ticket #{{ $ticket->id }} - {{ $ticket->subject }}</h1>
        <p class="muted">{{ $ticket->description }}</p>
        <form method="post" action="{{ route('apes-cic.tickets.update', $ticket) }}">
            @csrf
            @method('put')
            <div class="row">
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        @foreach(['open', 'in_progress', 'resolved', 'closed'] as $status)
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
                @if(auth()->user()->isStaff())
                    <div>
                        <label for="assigned_to">Assigned staff</label>
                        <select id="assigned_to" name="assigned_to">
                            <option value="">Unassigned</option>
                            @foreach($staffUsers as $staffUser)
                                <option value="{{ $staffUser->id }}" @selected((int)$ticket->assigned_to === (int)$staffUser->id)>{{ $staffUser->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
            <label for="message">Add message</label>
            <textarea id="message" name="message"></textarea>
            <div class="actions">
                <button type="submit">Save ticket</button>
                <a href="{{ route('apes-cic.tickets.index') }}">Back</a>
            </div>
        </form>
        @if(auth()->user()->isStaff())
            <form method="post" action="{{ route('apes-cic.tickets.destroy', $ticket) }}" onsubmit="return confirm('Delete this ticket?')">
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
