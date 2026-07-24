@extends('layouts.app')

@section('title', 'Shelter Cases')

@section('content')
    <div class="panel">
        <span class="service-label apes-shelter">APES Shelter and Rescue</span>
        <h1>Case management</h1>
        <p class="muted">Track adoption, surrender, rescue and fostering workflows.</p>
    </div>
    <div class="panel">
        <h2>Create case</h2>
        <form method="post" action="{{ route('shelter.cases.store') }}">
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
                    <label>Case type</label>
                    <select name="case_type">@foreach(['adoption','surrender','rescue','fostering'] as $type)<option value="{{ $type }}">{{ $type }}</option>@endforeach</select>
                </div>
            </div>
            <label>Title</label>
            <input name="title">
            <label>Details</label>
            <textarea name="details"></textarea>
            <button type="submit">Create case</button>
        </form>
    </div>
    <div class="panel">
        <h2>Cases</h2>
        <table>
            <thead><tr><th>ID</th><th>Title</th><th>Type</th><th>Status</th><th>Pet</th><th></th></tr></thead>
            <tbody>
            @foreach($cases as $case)
                <tr>
                    <td>#{{ $case->id }}</td>
                    <td>{{ $case->title }}</td>
                    <td>{{ $case->case_type }}</td>
                    <td><span class="status">{{ $case->status }}</span></td>
                    <td>{{ $case->petProfile->name }}</td>
                    <td><a href="{{ route('shelter.cases.show', $case) }}">Open</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $cases->links() }}
    </div>
@endsection
