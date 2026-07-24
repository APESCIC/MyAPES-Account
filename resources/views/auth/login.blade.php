@extends('layouts.app')

@section('title', 'Staff Sign in | MyAPES Account')

@section('content')
    <div class="panel">
        <img src="{{ asset('mascot/beardie-cozy.svg') }}" alt="Ember mascot highlighting APES services" class="hero-image">
        <h1>Staff sign in</h1>
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
        <form method="get" action="{{ route('staff.auth.login') }}">
            <button type="submit">Continue with APES Cloudron Login</button>
        </form>
    </div>
@endsection
