@extends('layouts.app')

@section('title', $ticketService->serviceName.' Tickets')

@section('content')
    <div class="panel">
        <span class="service-label {{ $ticketService->presentationClass }}">{{ $ticketService->serviceName }}</span>
        <h1>{{ $ticketService->heading }}</h1>
        <p class="muted">{{ $ticketService->supportingCopy }}</p>
    </div>
    @if($canCreateTicket)
        <div class="panel">
            <h2>Create ticket</h2>
            <form method="post" action="{{ route($ticketService->routePrefix.'.store') }}">
            @csrf
            <div class="row">
                <div>
                    <label for="service_area">Service area</label>
                    <select id="service_area" name="service_area">
                        @foreach($serviceAreas as $serviceArea)
                            <option value="{{ $serviceArea }}">{{ $serviceArea }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <option value="low">low</option>
                        <option value="medium">medium</option>
                        <option value="high">high</option>
                        <option value="urgent">urgent</option>
                    </select>
                </div>
            </div>
            <label for="subject">Subject</label>
            <input id="subject" name="subject" value="{{ old('subject') }}">
            <label for="description">Description</label>
            <textarea id="description" name="description">{{ old('description') }}</textarea>
                <button type="submit">Create ticket</button>
            </form>
        </div>
    @endif
    <div class="panel">
        <h2>Tickets</h2>
        <table>
            <thead><tr><th>ID</th><th>Subject</th><th>Area</th><th>Status</th><th>Priority</th><th>Owner</th><th></th></tr></thead>
            <tbody>
            @foreach($tickets as $ticket)
                <tr>
                    <td>#{{ $ticket->id }}</td>
                    <td>{{ $ticket->subject }}</td>
                    <td>{{ $ticket->service_area }}</td>
                    <td><span class="status">{{ $ticket->status }}</span></td>
                    <td>{{ $ticket->priority }}</td>
                    <td>{{ $ticket->user->name }}</td>
                    <td><a href="{{ route($ticketService->routePrefix.'.show', $ticket) }}">Open</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $tickets->links() }}
    </div>
@endsection
