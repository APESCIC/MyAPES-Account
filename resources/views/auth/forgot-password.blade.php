@extends('layouts.app')

@section('title', 'Forgot password | MyAPES Account')

@section('content')
    <div class="panel">
        <h1>Forgot password</h1>
        <p class="muted">Enter the email for your local public account. Directory and staff accounts should use Staff Login and Cloudron instead.</p>
        <x-mascot-tip />
        <form method="post" action="{{ route('password.email') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required>

            <div class="actions">
                <button type="submit">Send reset link</button>
                <a href="{{ route('public.login') }}">Back to Public Login</a>
            </div>
        </form>
    </div>
@endsection
