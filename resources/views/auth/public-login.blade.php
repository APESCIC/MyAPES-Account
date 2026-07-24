@extends('layouts.app')

@section('title', 'Public Login | MyAPES Account')

@section('content')
    <div class="panel">
        <img src="{{ asset('mascot/beardie-wave.svg') }}" alt="Bearded dragon mascot welcoming public users" class="brand-mascot">
        <h1>Public Login</h1>
        <p class="muted">Sign in to access your services, profile, and pet records.</p>
        <form method="post" action="{{ route('public.login.submit') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>

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
