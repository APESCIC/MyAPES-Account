@extends('layouts.app')

@section('title', 'Profile | MyAPES Core')

@section('content')
    <div class="panel">
        <h1>Your profile</h1>
        <p class="muted">Core account details used across all APES services.</p>
        <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('put')
            <div class="row">
                <div>
                    <label for="preferred_name">Preferred name</label>
                    <input id="preferred_name" name="preferred_name" value="{{ old('preferred_name', $profile?->preferred_name) }}">
                </div>
                <div>
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" value="{{ old('phone', $profile?->phone) }}">
                </div>
                <div>
                    <label for="organization">Organisation</label>
                    <input id="organization" name="organization" value="{{ old('organization', $profile?->organization) }}">
                </div>
            </div>
            <label for="support_needs">Support needs or access notes</label>
            <textarea id="support_needs" name="support_needs">{{ old('support_needs', $profile?->support_needs) }}</textarea>
            @include('profile._account-fields')
            <label for="avatar">Avatar photo</label>
            <input id="avatar" type="file" name="avatar" accept="image/*">
            <div class="actions">
                <button type="submit">Save profile</button>
            </div>
        </form>
    </div>
@endsection
