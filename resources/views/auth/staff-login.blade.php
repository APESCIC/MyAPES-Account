@extends('layouts.app')

@section('title', 'Staff Login | MyAPES Account')

@section('content')
    <div class="panel">
        <img src="{{ asset('branding/logo-myapes-account-horizontal.png') }}" alt="MyAPES Account staff login" class="hero-image">
        <h1>Staff Login</h1>
        <p class="muted">APES staff and administrators sign in via APES Cloudron.</p>
        <form method="get" action="{{ route('staff.auth.login') }}">
            <button type="submit">Continue with APES Cloudron Login</button>
        </form>
    </div>
@endsection
