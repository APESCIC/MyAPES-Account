@extends('layouts.app')

@section('title', 'Shelter Case #'.$case->id)

@section('content')
    <div class="panel">
        <span class="service-label apes-shelter">APES Shelter and Rescue</span>
        <h1>Case #{{ $case->id }} - {{ $case->title }}</h1>
        <p class="muted">Pet: {{ $case->petProfile->name }} | Type: {{ $case->case_type }}</p>
        @if($canUpdateCase || $canCloseCase || $canChangeAssignment)
            <form method="post" action="{{ route('shelter.cases.update', $case) }}">
                @csrf
                @method('put')
                <div class="row">
                    @if($canUpdateCase || $canCloseCase)
                        <div>
                            <label>Status</label>
                            <select name="status">
                                @foreach(['open', 'in_review', 'closed'] as $status)
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
                    @endif
                @if($canChangeAssignment)
                    <div>
                        <label>Assigned staff</label>
                        <select name="assigned_to">
                            <option value="">Unassigned</option>
                            @foreach($staffUsers as $staffUser)
                                <option value="{{ $staffUser->id }}" @selected((int)$case->assigned_to === (int)$staffUser->id)>{{ $staffUser->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                </div>
                @if($canUpdateCase)
                    <label>Details</label>
                    <textarea name="details">{{ $case->details }}</textarea>
                @endif
                <div class="actions">
                    <button type="submit">Update case</button>
                    <a href="{{ route('shelter.cases.index') }}">Back</a>
                </div>
            </form>
        @else
            <div class="actions"><a href="{{ route('shelter.cases.index') }}">Back</a></div>
        @endif
    </div>
@endsection
