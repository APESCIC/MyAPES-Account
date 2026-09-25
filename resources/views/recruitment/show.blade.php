@extends('layouts.app')

@section('title', $role->title.' | MyAPES Core')

@section('content')
    @include('recruitment._navigation')

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
            @auth
                <a href="{{ route('recruitment.applications.index') }}">My applications</a>
            @endauth
        </div>
    </div>

    <div class="panel" data-recruitment-apply>
        <h2>Apply for this role</h2>
        @if(! ($publicApplyEnabled ?? true))
            <p class="muted">Applications are not open for public roles right now. You can still browse open roles.</p>
        @elseif(auth()->guest())
            <p class="muted">Sign in or create a public account to apply. You can still browse open roles without an account.</p>
            <div class="actions">
                <a href="{{ route('public.login') }}">Public Login</a>
                <a href="{{ route('public.register') }}">Register</a>
            </div>
        @else
            @if($existingApplication)
                <p>
                    You already applied for this role.
                    Status: <span class="status">{{ $statusLabels[$existingApplication->status] ?? $existingApplication->status }}</span>
                </p>
                <div class="actions">
                    <a href="{{ route('recruitment.applications.show', $existingApplication) }}">View your application</a>
                </div>
            @elseif($canApply)
                <p class="muted">Each person may apply once per role. Tell us briefly why you are interested.</p>
                <form method="post" action="{{ route('recruitment.apply', $role) }}">
                    @csrf
                    <label for="application_statement">Why are you interested?</label>
                    <textarea id="application_statement" name="statement" required maxlength="5000">{{ old('statement') }}</textarea>
                    <div class="actions">
                        <button type="submit">Submit application</button>
                    </div>
                </form>
            @else
                <p class="muted">You cannot apply for this role with your current account.</p>
            @endif
        @endif
    </div>
@endsection
