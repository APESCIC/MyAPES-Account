@extends('layouts.app')

@section('title', __('auth.two_factor.challenge_title'))

@section('content')
    <div class="panel" id="two-factor-challenge">
        <h1>{{ __('auth.two_factor.challenge_heading') }}</h1>
        <p class="muted">{{ __('auth.two_factor.challenge_intro') }}</p>
        <x-mascot-tip />

        <form method="post" action="{{ route('two-factor.login.store') }}">
            @csrf
            <label for="code">{{ __('auth.two_factor.code_label') }}</label>
            <input
                id="code"
                type="text"
                name="code"
                value="{{ old('code') }}"
                inputmode="numeric"
                autocomplete="one-time-code"
                autofocus
            >

            <p class="muted">{{ __('auth.two_factor.or_recovery') }}</p>

            <label for="recovery_code">{{ __('auth.two_factor.recovery_code_label') }}</label>
            <input
                id="recovery_code"
                type="text"
                name="recovery_code"
                value="{{ old('recovery_code') }}"
                autocomplete="off"
            >

            <div class="actions">
                <button type="submit">{{ __('auth.two_factor.challenge_submit') }}</button>
                <a href="{{ route('public.login') }}">{{ __('auth.common.back_to_public_login') }}</a>
            </div>
        </form>
    </div>
@endsection
