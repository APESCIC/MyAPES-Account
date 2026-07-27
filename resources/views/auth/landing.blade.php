@extends('layouts.app')

@section('title', 'Welcome | MyAPES Account')

@section('content')
    <div class="panel">
        <img src="{{ asset('branding/logo-myapes-account.png') }}" alt="MyAPES Account" class="logo-hero">
        <img src="{{ asset('mascot/bearded-dragon-natural.png') }}" alt="A realistic bearded dragon resting in a natural habitat" class="hero-image" width="1400" height="1120">
        <h1>Welcome to MyAPES Account</h1>
        <p class="muted">Access support tools for APES CIC, APES Shelter and Rescue, and APES Pet Care.</p>
        <div class="grid">
            <div class="panel panel-flat">
                <span class="service-label apes-cic">APES CIC</span>
                <p>Organisational support ticketing for legal, HR, IT and web development assistance.</p>
            </div>
            <div class="panel panel-flat">
                <span class="service-label apes-shelter">APES Shelter and Rescue</span>
                <p>Pet profile management and case workflows for rescue, adoption, surrender and fostering.</p>
            </div>
            <div class="panel panel-flat">
                <span class="service-label apes-petcare">APES Pet Care</span>
                <p>Pet care support routes with consultation planning and follow-up management.</p>
            </div>
        </div>
    </div>
    <div class="grid">
        <div class="panel">
            <h2>Public access</h2>
            <p class="muted">For service users managing support, profiles, and pets.</p>
            <div class="actions">
                <a href="{{ route('public.login') }}">Public Login</a>
                <a href="{{ route('public.register') }}">Register</a>
            </div>
        </div>
        <div class="panel">
            <h2>Staff access</h2>
            <p class="muted">APES staff and administrators should use Cloudron sign-in.</p>
            <div class="actions">
                <a href="{{ route('staff.login') }}">Staff Login</a>
            </div>
        </div>
    </div>
@endsection
