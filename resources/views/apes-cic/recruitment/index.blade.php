@extends('layouts.app')

@section('title', 'Recruitment')

@section('content')
    <div class="panel">
        <span class="service-label service-apes-cic">APES CIC</span>
        <h1>Recruitment</h1>
        <p class="muted">Staff, volunteering, and student roles for APES CIC.</p>
        <x-mascot-tip />
    </div>
    <div class="panel" id="list">
        <h2>Roles</h2>
        <x-mascot-tip
            variant="empty"
            title="No recruitment roles yet."
            body="Role management will appear here once recruitment CRUD is available."
        />
    </div>
    @if($canCreate)
        <div class="panel" id="create">
            <h2>Create role</h2>
            <p class="muted">Role creation will be available in a later release.</p>
        </div>
    @endif
@endsection
