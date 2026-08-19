@extends('layouts.app')

@section('title', 'Public Login | MyAPES Account')

@section('content')
    <div class="panel">
        <h1>Public Login</h1>
        <p class="muted">Sign in to access your services, profile, and pet records.</p>
        <x-mascot-tip />
        <form method="post" action="{{ route('public.login.submit') }}">
            @csrf
            <label for="login">Username or email</label>
            <input id="login" type="text" name="login" value="{{ old('login') }}" required>

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>

            <label class="inline-check">
                <input type="checkbox" name="remember" value="1"> Remember me
            </label>

            <div class="actions">
                <button type="submit">Login</button>
                <a href="{{ route('public.register') }}">Create account</a>
                <a href="{{ route('staff.login') }}">Staff Login</a>
            </div>
        </form>
    </div>
@endsection
