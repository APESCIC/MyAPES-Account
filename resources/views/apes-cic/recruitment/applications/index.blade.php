@extends('layouts.app')

@section('title', 'Recruitment applications')

@section('content')
    @inject('ukDateTime', \App\Support\UkDateTime::class)
    <div class="panel">
        <span class="service-label service-apes-cic">APES CIC</span>
        <h1>Applications</h1>
        <p class="muted">Review applications for APES CIC recruitment roles.</p>
        <div class="actions">
            <a href="{{ route('apes-cic.recruitment.index') }}">Manage roles</a>
        </div>
        <x-mascot-tip />
    </div>

    <div class="panel" aria-label="Filter applications">
        <form method="get" action="{{ route('apes-cic.recruitment.applications.index') }}" class="stack-spaced">
            <div class="row">
                <div>
                    <label for="filter_status">Status</label>
                    <select id="filter_status" name="status">
                        <option value="">All statuses</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>
                                {{ $statusLabels[$status] ?? $status }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter_category">Category</label>
                    <select id="filter_category" name="category">
                        <option value="">All categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" @selected($selectedCategory === $category)>
                                {{ $category }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter_role">Role</label>
                    <select id="filter_role" name="role">
                        <option value="">All roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected($selectedRoleId === $role->id)>
                                {{ $role->title }} ({{ $role->status }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="actions">
                <button type="submit">Filter</button>
                <a href="{{ route('apes-cic.recruitment.applications.index') }}">Clear</a>
            </div>
        </form>
    </div>

    <div class="panel" id="list" data-staff-recruitment-applications>
        <h2>Applications</h2>
        @if($applications->isEmpty())
            <x-mascot-tip
                variant="empty"
                title="No applications match."
                body="Adjust the filters, or wait for applicants to apply to open roles."
            />
        @else
            <table>
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Applicant</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($applications as $application)
                        <tr>
                            <td>{{ $application->role?->title ?? '—' }}</td>
                            <td>
                                {{ $application->user?->name ?? '—' }}
                                @if($application->user)
                                    <br><small class="muted">{{ $application->user->email }}</small>
                                @endif
                            </td>
                            <td>{{ $statusLabels[$application->status] ?? $application->status }}</td>
                            <td>{{ $ukDateTime->formatDate($application->submitted_at) ?? '—' }}</td>
                            <td>
                                <a href="{{ route('apes-cic.recruitment.applications.show', $application) }}">Open</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $applications->links() }}
        @endif
    </div>
@endsection
