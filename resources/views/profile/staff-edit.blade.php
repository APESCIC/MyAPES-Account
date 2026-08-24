@extends('layouts.app')

@section('title', 'Staff profile | MyAPES Core')

@section('content')
    <div class="panel">
        <h1>Staff profile</h1>
        <p class="muted">Directory name, email, and groups stay read-only. Add the workplace details colleagues need.</p>
        <dl class="admin-definition-list">
            <div><dt>Name</dt><dd>{{ auth()->user()->name }}</dd></div>
            <div><dt>Email</dt><dd>{{ auth()->user()->email }}</dd></div>
            <div class="admin-definition-list__groups">
                <dt>Directory groups</dt>
                <dd>
                    <x-directory-group-list :groups="auth()->user()->ldap_groups ?? []" />
                </dd>
            </div>
        </dl>
        <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('put')
            <label for="job_title">Job title</label>
            <input id="job_title" name="job_title" value="{{ old('job_title', $staffProfile?->job_title) }}">
            <label for="team">Team</label>
            <select id="team" name="team">
                <option value="">Select a team</option>
                @foreach($teams as $value => $label)
                    <option value="{{ $value }}" @selected(old('team', $staffProfile?->team) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <label for="work_phone">Work phone</label>
            <input id="work_phone" name="work_phone" value="{{ old('work_phone', $staffProfile?->work_phone) }}" placeholder="+447700900123">
            @if($staffProfile?->photo_path)
                <p>
                    <img src="{{ route('profile.staff-photo') }}" alt="Current staff photo" width="96" height="96">
                </p>
            @endif
            <label for="photo">Staff photo</label>
            <input id="photo" type="file" name="photo" accept="image/*">
            <div class="actions">
                <button type="submit">Save staff profile</button>
            </div>
        </form>
    </div>
@endsection
