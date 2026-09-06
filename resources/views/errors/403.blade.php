@extends('layouts.app')

@section('title', 'Access denied | MyAPES Core')

@section('content')
    <div class="panel">
        <h1>Access denied</h1>
        <p class="muted">
            {{ $exception->getMessage() !== '' ? $exception->getMessage() : 'You do not have permission to view this page. If you need access, contact an APES administrator.' }}
        </p>
        <div class="actions">
            <a href="{{ route('home') }}">Back to home</a>
            @auth
                <a href="{{ route('dashboard') }}">Go to dashboard</a>
            @endauth
        </div>
    </div>
@endsection
