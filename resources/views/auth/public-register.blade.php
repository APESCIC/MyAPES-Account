@extends('layouts.app')

@section('title', 'Register | MyAPES Account')

@section('content')
    <div class="panel">
        <h1>Create public account</h1>
        <p class="muted">Register to access services, your profile, and your pets.</p>
        <form method="post" action="{{ route('public.register.submit') }}">
            @csrf
            <label for="name">Full name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required>

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>

            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required>

            <div class="actions" style="margin-top:.8rem;">
                <button type="submit">Register</button>
                <a href="{{ route('public.login') }}">Already have an account?</a>
            </div>
        </form>
    </div>
@endsection
