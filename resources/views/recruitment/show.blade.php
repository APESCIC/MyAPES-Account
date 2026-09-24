@extends('layouts.app')

@section('title', $role->title.' | MyAPES Core')

@section('content')
    <div class="panel" data-recruitment-role-detail>
        <span class="service-label service-apes-cic">{{ $categoryLabels[$role->category] }}</span>
        <h1>{{ $role->title }}</h1>
        @if($role->summary)
            <p class="muted">{{ $role->summary }}</p>
        @endif
        @if($role->location || $role->commitment)
            <p class="muted">
                @if($role->location)Location: {{ $role->location }}@endif
                @if($role->location && $role->commitment) · @endif
                @if($role->commitment)Commitment: {{ $role->commitment }}@endif
            </p>
        @endif
        <div class="stack-spaced">
            {!! nl2br(e($role->description)) !!}
        </div>
        <div class="actions">
            <a href="{{ route('recruitment.index') }}">Back to open roles</a>
        </div>
    </div>
@endsection
