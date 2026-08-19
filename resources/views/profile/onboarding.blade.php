@extends('layouts.app')

@section('title', 'Complete account setup | MyAPES Account')

@section('content')
    <div class="panel">
        <h1>Complete your account setup</h1>
        <p class="muted">Confirm your UK contact details, services, and optional contact choices.</p>
        <x-mascot-tip />
        <form method="post" action="{{ route('onboarding.update') }}">
            @csrf
            @method('put')
            @include('profile._account-fields')
            <button type="submit">Complete setup</button>
        </form>
    </div>
@endsection
