@extends('layouts.app')

@section('title', 'Staff Sign in | MyAPES Account')

@section('content')
    <div class="panel">
        <img src="{{ asset('mascot/bearded-dragon-natural.png') }}" alt="A realistic bearded dragon resting in a natural habitat" class="hero-image" width="1400" height="1120">
        <h1>Staff sign in</h1>
        <p class="muted">Access support tools for APES CIC, APES Shelter and Rescue, and APES Pet Care Clinic.</p>
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
                <span class="service-label apes-petcare">APES Pet Care Clinic</span>
                <p>Pet Profiles, Tickets, and Consultations for clinic planning and follow-up.</p>
            </div>
        </div>
        <form method="get" action="{{ route('staff.auth.login') }}">
            <button type="submit">Continue with APES Cloudron Login</button>
        </form>
    </div>
@endsection
