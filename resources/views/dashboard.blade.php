@extends('layouts.app')

@section('title', 'Dashboard | MyAPES Account')

@section('content')
    <div class="panel">
        <img src="{{ asset('mascot/beardie-cozy.svg') }}" alt="MyAPES mascot celebrating progress" class="hero-image">
        <h1>Dashboard</h1>
        <p class="muted">Welcome back, {{ auth()->user()->name }}. Role: <strong>{{ auth()->user()->role }}</strong></p>
    </div>
    <div class="grid">
        <div class="panel">
            <span class="service-label apes-cic">APES CIC</span>
            <h3>{{ $ticketCount }} ticket(s)</h3>
            <a href="{{ route('apes-cic.tickets.index') }}">Open ticket workspace</a>
        </div>
        <div class="panel">
            <span class="service-label apes-shelter">APES Shelter and Rescue</span>
            <h3>{{ $shelterCaseCount }} case(s)</h3>
            <a href="{{ route('shelter.cases.index') }}">Open case management</a>
        </div>
        <div class="panel">
            <span class="service-label apes-petcare">APES Pet Care</span>
            <h3>{{ $consultationCount }} consultation(s)</h3>
            <a href="{{ route('petcare.consultations.index') }}">Open consultations</a>
        </div>
        <div class="panel">
            <h3>{{ $petProfileCount }} pet profile(s)</h3>
            <p class="muted">Combined across APES Shelter and APES Pet Care.</p>
        </div>
    </div>
@endsection
