@extends('layouts.app')

@section('title', 'Reset password | MyAPES Account')

@section('content')
    <div class="panel">
        <h1>Reset password</h1>
        <p class="muted">Choose a new password for your local public account.</p>
        <x-mascot-tip />
        <form method="post" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" autocomplete="username" required>

            <label for="password">New password</label>
            <input id="password" type="password" name="password" autocomplete="new-password" required>

            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>

            <div class="actions">
                <button type="submit">Reset password</button>
                <a href="{{ route('public.login') }}">Back to Public Login</a>
            </div>
        </form>
    </div>
@endsection
