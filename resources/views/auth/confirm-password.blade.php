@extends('layouts.app')

@section('title', __('auth.confirm_password.title'))

@section('content')
    <div class="panel" data-passkeys-confirm>
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

        @if(auth()->user()?->isLocalPasswordIdentity() && auth()->user()?->hasPasskeysEnabled())
            <div class="passkey-confirm mt-2">
                <p class="muted">{{ __('auth.passkeys.confirm_intro') }}</p>
                <p class="error" role="alert" hidden data-passkey-error></p>
                <div class="actions">
                    <button type="button" data-passkey-confirm>{{ __('auth.passkeys.confirm_button') }}</button>
                </div>
            </div>
        @endif
    </div>
@endsection
