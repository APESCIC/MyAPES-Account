@extends('layouts.app')

@section('title', 'APES Pet Care Clinic Consultation #'.$consultation->id)

@section('content')
    <div class="panel">
        <span class="service-label apes-petcare">APES Pet Care Clinic</span>
        <h1>Consultation #{{ $consultation->id }} - {{ $consultation->subject }}</h1>
        <p class="muted">Pet: {{ $consultation->petProfile->name }}</p>
        <dl>
            <dt>Status</dt>
            <dd>{{ $consultation->status }}</dd>
            <dt>Scheduled for</dt>
            <dd>{{ $consultation->scheduled_for ?: 'Not scheduled' }}</dd>
            <dt>Notes</dt>
            <dd>{{ $consultation->notes ?: 'No notes recorded.' }}</dd>
            @if($canAssign)
                <dt>Assigned staff</dt>
                <dd>
                    @if($consultation->assignedTo)
                        {{ $consultation->assignedTo->name }}
                        @if($currentAssigneeUnavailable)
                            <span class="muted">Current assignment is preserved but is no longer eligible.</span>
                        @endif
                    @else
                        Unassigned
                    @endif
                </dd>
            @endif
        </dl>
        @if($canUpdate || $canClose)
            <form id="consultation-update-form" method="post" action="{{ route('petcare.consultations.update', $consultation) }}">
                @csrf
                @method('put')
                <div class="row">
                    @if(($consultation->status === 'closed' && $canClose) || ($consultation->status !== 'closed' && ($canUpdate || $canClose)))
                        <div>
                            <label>Status</label>
                            <select name="status">
                                @foreach(['open','in_progress','closed'] as $status)
                                    @if($status === $consultation->status || ($status === 'closed' ? $canClose : ($consultation->status === 'closed' ? $canClose : $canUpdate)))
                                        <option value="{{ $status }}" @selected($consultation->status===$status)>{{ $status }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if($canUpdate)
                        <div>
                            <label>Scheduled for</label>
                            <input type="datetime-local" name="scheduled_for" step="1" value="{{ $consultation->scheduled_for ? $consultation->scheduled_for->format('Y-m-d\\TH:i:s') : '' }}">
                        </div>
                    @endif
                </div>
                @if($canUpdate)
                    <label>Notes</label>
                    <textarea name="notes">{{ $consultation->notes }}</textarea>
                @endif
                <div class="actions">
                    <button type="submit">Update consultation</button>
                </div>
            </form>
        @endif
        @if($canAssign)
            <form id="consultation-assignment-form" method="post" action="{{ route('petcare.consultations.update', $consultation) }}">
                @csrf
                @method('put')
                <label>Change assigned staff</label>
                <select name="assigned_to">
                    <option disabled selected>Choose an assignment change</option>
                    <option value="">Clear assignment</option>
                    @foreach($staffUsers as $staffUser)
                        <option value="{{ $staffUser->id }}">{{ $staffUser->name }}</option>
                    @endforeach
                </select>
                <div class="actions">
                    <button type="submit">Update assignment</button>
                </div>
            </form>
        @endif
        <a href="{{ route('petcare.consultations.index') }}">Back</a>
    </div>
@endsection
