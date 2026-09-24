@extends('layouts.app')

@section('title', 'Review application')

@section('content')
    @inject('ukDateTime', \App\Support\UkDateTime::class)
    <div class="panel" data-staff-recruitment-application-detail>
        <span class="service-label service-apes-cic">APES CIC</span>
        <h1>{{ $application->recruitmentRole?->title ?? 'Application' }}</h1>
        <p class="muted">Status: {{ $statusLabels[$application->status] ?? $application->status }}</p>
        <dl class="ticket-meta">
            <div>
                <dt>Applicant</dt>
                <dd>
                    {{ $application->user?->name ?? '—' }}
                    @if($application->user)
                        <br><small class="muted">{{ $application->user->email }}</small>
                    @endif
                </dd>
            </div>
            <div>
                <dt>Category</dt>
                <dd>{{ $application->recruitmentRole?->category ?? '—' }}</dd>
            </div>
            <div>
                <dt>Submitted</dt>
                <dd>{{ $ukDateTime->formatDate($application->submitted_at) ?? '—' }}</dd>
            </div>
            <div>
                <dt>Application ID</dt>
                <dd>#{{ $application->id }}</dd>
            </div>
            @if($application->reviewed_at)
                <div>
                    <dt>Review started</dt>
                    <dd>{{ $ukDateTime->formatDate($application->reviewed_at) }}</dd>
                </div>
            @endif
            @if($application->decided_at)
                <div>
                    <dt>Decided</dt>
                    <dd>{{ $ukDateTime->formatDate($application->decided_at) }}</dd>
                </div>
            @endif
            @if($application->withdrawn_at)
                <div>
                    <dt>Withdrawn</dt>
                    <dd>{{ $ukDateTime->formatDate($application->withdrawn_at) }}</dd>
                </div>
            @endif
        </dl>
        @if($application->statement)
            <h2>Applicant statement</h2>
            <div class="stack-spaced">
                {!! nl2br(e($application->statement)) !!}
            </div>
        @endif
        @if($application->staff_notes && ! $canReview)
            <h2>Staff notes</h2>
            <div class="stack-spaced">
                {!! nl2br(e($application->staff_notes)) !!}
            </div>
        @endif
        @if($canReview)
            <form method="post" action="{{ route('apes-cic.recruitment.applications.update', $application) }}" class="stack-spaced">
                @csrf
                @method('put')
                <label for="review_status">Update status</label>
                <select id="review_status" name="status" required>
                    @foreach($allowedTransitions as $status)
                        <option value="{{ $status }}" @selected(old('status') === $status)>
                            {{ $statusLabels[$status] ?? $status }}
                        </option>
                    @endforeach
                </select>
                <label for="staff_notes">Staff notes</label>
                <textarea id="staff_notes" name="staff_notes" maxlength="5000">{{ old('staff_notes', $application->staff_notes) }}</textarea>
                <div class="actions">
                    <button type="submit">Save review</button>
                </div>
            </form>
        @endif
        <div class="actions">
            <a href="{{ route('apes-cic.recruitment.applications.index') }}">Back to applications</a>
            @if($application->recruitmentRole)
                <a href="{{ route('apes-cic.recruitment.show', $application->recruitmentRole) }}">View role</a>
            @endif
        </div>
    </div>
@endsection
