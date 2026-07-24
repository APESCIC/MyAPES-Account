@extends('layouts.app')

@section('title', 'Shelter Case #'.$case->id)

@section('content')
    <div class="panel">
        <span class="service-label apes-shelter">APES Shelter and Rescue</span>
        <h1>Case #{{ $case->id }} - {{ $case->title }}</h1>
        <p class="muted">Pet: {{ $case->petProfile->name }} | Type: {{ $case->case_type }}</p>
        <form method="post" action="{{ route('shelter.cases.update', $case) }}">
            @csrf
            @method('put')
            <div class="row">
                <div>
                    <label>Status</label>
                    <select name="status">@foreach(['open','in_review','closed'] as $status)<option value="{{ $status }}" @selected($case->status===$status)>{{ $status }}</option>@endforeach</select>
                </div>
                @if(auth()->user()->isStaff())
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
            <label>Details</label>
            <textarea name="details">{{ $case->details }}</textarea>
            <div class="actions">
                <button type="submit">Update case</button>
                <a href="{{ route('shelter.cases.index') }}">Back</a>
            </div>
        </form>
    </div>
@endsection
