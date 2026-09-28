@extends('layouts.app')

@section('title', 'Shelter Case #'.$case->id)

@section('content')
    <div class="panel">
        <span class="service-label apes-shelter">APES Shelter and Rescue</span>
        <h1>Case #{{ $case->id }} - {{ $case->title }}</h1>
        <p class="muted">Pet: {{ $case->petProfile->name }} | Type: {{ $case->case_type }}</p>
        <p class="muted">Status: {{ $case->status }}</p>
        @if(filled($case->details))
            <div>
                <strong>Details</strong>
                <p>{{ $case->details }}</p>
            </div>
        @endif
        @if($canUpdateCase || $canCloseCase)
            <form id="case-metadata-form" method="post" action="{{ route('shelter.cases.update', $case) }}">
                @csrf
                @method('put')
                @if($canUpdateCase)
                    <div class="row">
                        <div>
                            <label for="case_type">Case type</label>
                            <select id="case_type" name="case_type">
                                @foreach($caseTypes as $type)
                                    <option value="{{ $type }}" @selected($case->case_type === $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="title">Title</label>
                            <input id="title" name="title" value="{{ $case->title }}">
                        </div>
                    </div>
                @endif
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        @foreach($statuses as $status)
                            @php
                                $isCurrentStatus = $status === $case->status;
                                $crossesClosedBoundary = $status === 'closed'
                                    || $case->status === 'closed';
                            @endphp
                            @if($isCurrentStatus
                                || ($crossesClosedBoundary && $canCloseCase)
                                || (! $crossesClosedBoundary && $canUpdateCase))
                                <option value="{{ $status }}" @selected($isCurrentStatus)>{{ $status }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                @if($canUpdateCase)
                    <label for="details">Details</label>
                    <textarea id="details" name="details">{{ $case->details }}</textarea>
                @endif
                <button type="submit">Update case</button>
            </form>
        @endif
        @if($canChangeAssignment)
            <form id="case-assignment-form" method="post" action="{{ route('shelter.cases.update', $case) }}">
                @csrf
                @method('put')
                <div>
                    <label for="assigned_to">Assigned staff</label>
                    <select id="assigned_to" name="assigned_to">
                        <option value="">Unassigned</option>
                        @foreach($staffUsers as $staffUser)
                            <option value="{{ $staffUser->id }}" @selected((int)$case->assigned_to === (int)$staffUser->id)>{{ $staffUser->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit">Update assignment</button>
            </form>
        @endif
        <div class="actions">
            <a href="{{ route('shelter.cases.index') }}">Back</a>
        </div>

        @if($canCommentCase)
            <form method="post" action="{{ route('shelter.cases.updates.store', $case) }}">
                @csrf
                <label for="body">Add update</label>
                <textarea id="body" name="body"></textarea>
                @if($canChooseVisibility)
                    <label for="visibility">Visibility</label>
                    <select id="visibility" name="visibility">
                        <option value="public">Public</option>
                        <option value="internal">Internal staff only</option>
                    </select>
                @endif
                <button type="submit">Add update</button>
            </form>
        @elseif($case->status === 'closed')
            <p class="muted">Reopen this case before adding another update.</p>
        @endif
    </div>
    <div class="panel">
        <h2>Activity</h2>
        @forelse($updates as $update)
            <div class="item-divider">
                <strong>{{ $update->user?->name ?? 'Former user' }}</strong>
                <span class="muted">{{ $update->created_at }}</span>
                @if($update->visibility === 'internal')<span class="status">internal</span>@endif
                <div>{{ $update->body }}</div>
            </div>
        @empty
            <p class="muted">No updates have been added.</p>
        @endforelse
    </div>
@endsection
