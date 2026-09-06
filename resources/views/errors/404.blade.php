@extends('layouts.app')

@section('title', 'Page not found | MyAPES Core')

@section('content')
    <div class="panel">
        <h1>Page not found</h1>
        <p class="muted">That address is not available in MyAPES Core. Check the link, or return home to continue.</p>
        <div class="actions">
            <a href="{{ route('home') }}">Back to home</a>
            @auth
                <a href="{{ route('dashboard') }}">Go to dashboard</a>
            @endauth
        </div>
    </div>
@endsection
