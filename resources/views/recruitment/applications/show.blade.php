@extends('layouts.app')

@section('title', 'Application | MyAPES Core')

@section('content')
    @include('recruitment._navigation')

    @inject('ukDateTime', \App\Support\UkDateTime::class)
    <div class="panel" data-recruitment-application-detail>
        <span class="service-label service-apes-cic">APES CIC</span>
        <h1>{{ $application->recruitmentRole?->title ?? 'Application' }}</h1>
        <p class="muted">Status: {{ $statusLabels[$application->status] ?? $application->status }}</p>
        <dl class="ticket-meta">
            <div>
                <dt>Submitted</dt>
                <dd>{{ $ukDateTime->formatDate($application->submitted_at) ?? '—' }}</dd>
            </div>
            <div>
                <dt>Application ID</dt>
                <dd>#{{ $application->id }}</dd>
            </div>
            @if($application->withdrawn_at)
                <div>
                    <dt>Withdrawn</dt>
                    <dd>{{ $ukDateTime->formatDate($application->withdrawn_at) }}</dd>
                </div>
            @endif
        </dl>
        @if($application->statement)
            <h2>Your statement</h2>
            <div class="stack-spaced">
                {!! nl2br(e($application->statement)) !!}
            </div>
        @endif
        <div class="actions">
            <a href="{{ route('recruitment.applications.index') }}">Back to my applications</a>
            @if($application->recruitmentRole?->isOpen())
                <a href="{{ route('recruitment.show', $application->recruitmentRole) }}">View role</a>
            @endif
        </div>
        @if($canWithdraw)
            <form method="post" action="{{ route('recruitment.applications.withdraw', $application) }}">
                @csrf
                <button type="submit">Withdraw application</button>
            </form>
        @endif
    </div>
@endsection
