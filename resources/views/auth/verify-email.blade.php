@extends('layouts.app')

@section('title', 'Verify email | MyAPES Account')

@section('content')
    <div class="panel">
        <h1>Verify your email</h1>
        <p class="muted">Use the signed link sent to your email before continuing account setup.</p>
        <x-mascot-tip />
        <form method="post" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit">Send another verification link</button>
        </form>
        <form method="post" action="{{ route('auth.logout') }}">
            @csrf
            <button type="submit" class="button-secondary">Log out</button>
        </form>
    </div>
@endsection
