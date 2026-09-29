@extends('layouts.app')

@section('title', __('auth.public_login.title'))

@section('content')
    <div class="panel">
        <h1>{{ __('auth.public_login.heading') }}</h1>
        <p class="muted">{{ __('auth.public_login.intro') }}</p>
        <x-mascot-tip />
        <form method="post" action="{{ route('public.login.submit') }}">
            @csrf
            <label for="login">{{ __('auth.public_login.login_label') }}</label>
            <input id="login" type="text" name="login" value="{{ old('login') }}" autocomplete="username" required>

            <label for="password">{{ __('auth.common.password') }}</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>

            <label class="inline-check">
                <input type="checkbox" name="remember" value="1"> {{ __('auth.common.remember_me') }}
            </label>

            <div class="actions">
                <button type="submit">{{ __('auth.common.login') }}</button>
                <a href="{{ route('password.request') }}">{{ __('auth.common.forgot_password_link') }}</a>
                <a href="{{ route('public.register') }}">{{ __('auth.common.create_account') }}</a>
                <a href="{{ route('staff.login') }}">{{ __('auth.common.staff_login') }}</a>
            </div>
        </form>
    </div>
@endsection
