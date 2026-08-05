@extends('layouts.app')

@section('title', 'Consultation #'.$consultation->id)

@section('content')
    <div class="panel">
        <span class="service-label apes-petcare">APES Pet Care</span>
        <h1>Consultation #{{ $consultation->id }} - {{ $consultation->subject }}</h1>
        <p class="muted">Pet: {{ $consultation->petProfile->name }}</p>
        <form method="post" action="{{ route('petcare.consultations.update', $consultation) }}">
            @csrf
            @method('put')
            <div class="row">
                <div>
                    <label>Status</label>
                    <select name="status">@foreach(['open','in_progress','closed'] as $status)<option value="{{ $status }}" @selected($consultation->status===$status)>{{ $status }}</option>@endforeach</select>
                </div>
                <div>
                    <label>Scheduled for</label>
                    <input type="datetime-local" name="scheduled_for" value="{{ $consultation->scheduled_for ? $consultation->scheduled_for->format('Y-m-d\\TH:i') : '' }}">
                </div>
                @if($canChangeAssignment)
                    <div>
                        <label>Assigned staff</label>
                        <select name="assigned_to">
                            <option value="">Unassigned</option>
                            @foreach($staffUsers as $staffUser)
                                <option value="{{ $staffUser->id }}" @selected((int)$consultation->assigned_to === (int)$staffUser->id)>{{ $staffUser->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
            <label>Notes</label>
            <textarea name="notes">{{ $consultation->notes }}</textarea>
            <div class="actions">
                <button type="submit">Update consultation</button>
                <a href="{{ route('petcare.consultations.index') }}">Back</a>
            </div>
        </form>
    </div>
@endsection
