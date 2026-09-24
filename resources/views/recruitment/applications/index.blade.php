@extends('layouts.app')

@section('title', 'My applications | MyAPES Core')

@section('content')
    @inject('ukDateTime', \App\Support\UkDateTime::class)
    <div class="panel">
        <span class="service-label service-apes-cic">APES CIC</span>
        <h1>My applications</h1>
        <p class="muted">Roles you have applied for, with their current status.</p>
        <div class="actions">
            <a href="{{ route('recruitment.index') }}">Browse open roles</a>
        </div>
    </div>

    <div class="panel" id="list" data-recruitment-applications>
        <h2>Applications</h2>
        @if($applications->isEmpty())
            <x-mascot-tip
                variant="empty"
                title="No applications yet."
                body="When you apply for an open role, it will appear here."
            />
        @else
            <table>
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($applications as $application)
                        <tr>
                            <td>{{ $application->role?->title ?? 'Role removed' }}</td>
                            <td>{{ $statusLabels[$application->status] ?? $application->status }}</td>
                            <td>{{ $ukDateTime->formatDate($application->submitted_at) ?? '—' }}</td>
                            <td>
                                <a href="{{ route('recruitment.applications.show', $application) }}">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $applications->links() }}
        @endif
    </div>
@endsection
