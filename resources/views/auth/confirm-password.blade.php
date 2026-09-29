@extends('layouts.app')

@section('title', __('auth.confirm_password.title'))

@section('content')
    <div class="panel">
        <h1>{{ __('auth.confirm_password.heading') }}</h1>
        <p class="muted">{{ __('auth.confirm_password.intro') }}</p>
        <x-mascot-tip />
        <form method="post" action="{{ route('password.confirm.store') }}">
            @csrf
            <label for="password">{{ __('auth.common.password') }}</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required autofocus>

            <div class="actions">
                <button type="submit">{{ __('auth.confirm_password.submit') }}</button>
                <a href="{{ route('profile.edit') }}">{{ __('auth.confirm_password.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
