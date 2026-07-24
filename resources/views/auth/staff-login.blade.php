@extends('layouts.app')

@section('title', 'Staff Login | MyAPES Account')

@section('content')
    <div class="panel">
        <img src="{{ asset('branding/logo-myapes-account-wordmark.png') }}" alt="MyAPES Account staff login" class="logo-hero">
        <h1>Staff Login</h1>
        <p class="muted">APES staff and administrators sign in via APES Cloudron.</p>
        @if(app()->environment(['local', 'testing']))
            <p class="muted">Local QA mode: use seeded staff/admin credentials to sign in directly.</p>
            <form method="post" action="{{ route('staff.local-login.submit') }}">
                @csrf
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required>

                <label for="password">Password</label>
                <input id="password" type="password" name="password" required>

                <label style="display:flex;align-items:center;gap:.5rem;">
                    <input type="checkbox" name="remember" value="1" style="width:auto;"> Remember me
                </label>

                <div class="actions" style="margin-top:.8rem;">
                    <button type="submit">Local Staff Login</button>
                </div>
            </form>
            <hr style="border:0;border-top:1px solid #e2e8f0;margin:1rem 0;">
        @endif
        <form method="get" action="{{ route('staff.auth.login') }}">
            <button type="submit">Continue with APES Cloudron Login</button>
        </form>
    </div>
@endsection
