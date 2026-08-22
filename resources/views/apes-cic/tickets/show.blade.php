@extends('layouts.app')

@section('title', 'Ticket #'.$ticket->id)

@section('content')
    <div class="panel">
        <span class="service-label {{ $ticketService->presentationClass }}">{{ $ticketService->serviceName }}</span>
        <h1>Ticket #{{ $ticket->id }} - {{ $ticket->subject }}</h1>
        <p class="muted">{{ $ticket->description }}</p>

        <dl class="ticket-meta">
            <div>
                <dt>Owner</dt>
                <dd>{{ $ticket->user?->name ?? '—' }}@if($ticket->user)<br><small class="muted">{{ $ticket->user->email }}</small>@endif</dd>
            </div>
            <div>
                <dt>Assigned staff</dt>
                <dd>{{ $ticket->assignedTo?->name ?? 'Unassigned' }}</dd>
            </div>
            <div>
                <dt>Service area</dt>
                <dd>
                    @if($usesHierarchicalCategories)
                        {{ $categoryResolver->labelForArea($ticket->sub_core_key, $ticket->service_area) }}
                    @else
                        {{ $ticket->service_area }}
                    @endif
                </dd>
            </div>
            @if($usesHierarchicalCategories && $ticket->sub_category)
                <div>
                    <dt>Subcategory</dt>
                    <dd>{{ $categoryResolver->labelForSubcategory($ticket->sub_core_key, $ticket->service_area, $ticket->sub_category) }}</dd>
                </div>
            @endif
            @if($usesHierarchicalCategories && $ticket->affected_website_key)
                <div>
                    <dt>Affected website</dt>
                    <dd>{{ $categoryResolver->labelForWebsite($ticket->sub_core_key, $ticket->affected_website_key) }}</dd>
                </div>
            @endif
            <div>
                <dt>Status</dt>
                <dd><span class="status">{{ $ticket->status }}</span></dd>
            </div>
            <div>
                <dt>Priority</dt>
                <dd>{{ $ticket->priority }}</dd>
            </div>
        </dl>

        @if($canUpdateTicket || $canCommentTicket)
            <form id="ticket-workflow-form" method="post" action="{{ route($ticketService->routePrefix.'.update', $ticket) }}" @if($allowsAttachments) enctype="multipart/form-data" @endif>
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
                @if($allowsAttachments && $canCommentTicket)
                    <label for="screenshots">Add screenshots</label>
                    <input id="screenshots" name="screenshots[]" type="file" accept="image/jpeg,image/png,image/webp" multiple>
                    <label for="screencast">Add screencast</label>
                    <input id="screencast" name="screencast" type="file" accept="video/mp4,video/webm">
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
                <div class="row">
                    <div>
                        <label for="user_id">Owner</label>
                        <select id="user_id" name="user_id">
                            @foreach($ownerCandidates as $ownerCandidate)
                                <option value="{{ $ownerCandidate->id }}" @selected((int) $ticket->user_id === (int) $ownerCandidate->id)>
                                    {{ $ownerCandidate->name }} ({{ $ownerCandidate->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="assigned_to">Assigned staff</label>
                        <select id="assigned_to" name="assigned_to">
                            <option value="">Unassigned</option>
                            @foreach($staffUsers as $staffUser)
                                <option value="{{ $staffUser->id }}" @selected((int)$ticket->assigned_to === (int)$staffUser->id)>{{ $staffUser->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="submit">Update ownership</button>
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

    @if($ticket->attachments->isNotEmpty())
        <div class="panel">
            <h2>Attachments</h2>
            <ul class="attachment-list">
                @foreach($ticket->attachments as $attachment)
                    <li>
                        <strong>{{ $attachment->kind }}</strong>
                        — {{ $attachment->original_name }}
                        <span class="muted">({{ number_format($attachment->size_bytes / 1024, 1) }} KB)</span>
                        <a href="{{ route('support.attachments.download', $attachment) }}">Open</a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

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
