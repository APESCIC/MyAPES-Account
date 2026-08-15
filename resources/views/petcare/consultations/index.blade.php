@extends('layouts.app')

@section('title', 'APES Pet Care Clinic Consultations')

@section('content')
    <div class="panel">
        <span class="service-label apes-petcare">APES Pet Care Clinic</span>
        <h1>Consultation management</h1>
    </div>
    @if($canCreate)
        <div class="panel">
            <h2>Create consultation</h2>
            <form method="post" action="{{ route('petcare.consultations.store') }}">
            @csrf
            <div class="row">
                <div>
                    <label>Pet profile</label>
                    <select name="pet_profile_id">
                        @foreach($petProfiles as $petProfile)
                            <option value="{{ $petProfile->id }}">{{ $petProfile->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Scheduled for</label>
                    <input type="datetime-local" name="scheduled_for">
                </div>
            </div>
            <label>Subject</label>
            <input name="subject">
            <label>Notes</label>
            <textarea name="notes"></textarea>
            <button type="submit">Create consultation</button>
            </form>
        </div>
    @endif
    <div class="panel">
        <h2>Consultations</h2>
        <table>
            <thead><tr><th>ID</th><th>Subject</th><th>Status</th><th>Pet</th><th>Scheduled</th><th></th></tr></thead>
            <tbody>
            @foreach($consultations as $consultation)
                <tr>
                    <td>#{{ $consultation->id }}</td>
                    <td>{{ $consultation->subject }}</td>
                    <td><span class="status">{{ $consultation->status }}</span></td>
                    <td>{{ $consultation->petProfile->name }}</td>
                    <td>{{ $consultation->scheduled_for }}</td>
                    <td><a href="{{ route('petcare.consultations.show', $consultation) }}">Open</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $consultations->links() }}
    </div>
@endsection
