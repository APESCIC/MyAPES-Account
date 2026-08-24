@extends('layouts.app')

@section('title', 'Admin users | MyAPES Core')

@section('content')
    @include('admin._navigation')

    <section class="panel" aria-labelledby="admin-users-title">
        <h1 id="admin-users-title">
            @if(($filters['account_type'] ?? '') === 'staff')
                Staff
            @elseif(($filters['account_type'] ?? '') === 'public')
                Public users
            @else
                Users
            @endif
        </h1>
        <p class="muted">Search accounts and review their identity, status, and effective protected role. Directory-owned names and email addresses are read-only.</p>
        <p>
            <a href="{{ route('admin.users.index', ['account_type' => 'public']) }}">Public users</a>
            ·
            <a href="{{ route('admin.users.index', ['account_type' => 'staff']) }}">Staff</a>
            ·
            <a href="{{ route('admin.users.index') }}">All accounts</a>
        </p>

        <form method="get" action="{{ route('admin.users.index') }}">
            @if(! empty($filters['account_type']))
                <input type="hidden" name="account_type" value="{{ $filters['account_type'] }}">
            @endif
            <div class="row">
                <div>
                    <label for="user-search">Search name or email</label>
                    <input id="user-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100">
                </div>
                <div>
                    <label for="identity-type">Identity source</label>
                    <select id="identity-type" name="identity_type">
                        <option value="">All identity sources</option>
                        @foreach($identityTypes as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['identity_type'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="user-status">Status</label>
                    <select id="user-status" name="status">
                        <option value="">All statuses</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                        <option value="suspended" @selected(($filters['status'] ?? '') === 'suspended')>Suspended</option>
                    </select>
                </div>
                <div>
                    <label for="protected-role">Protected role</label>
                    <select id="protected-role" name="protected_role">
                        <option value="">All protected roles</option>
                        @foreach($protectedRoles as $role)
                            <option value="{{ $role }}" @selected(($filters['protected_role'] ?? '') === $role)>{{ $role }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="actions">
                <button type="submit">Apply filters</button>
                <a href="{{ route('admin.users.index') }}">Clear filters</a>
            </div>
        </form>
    </section>

    <section class="panel" aria-labelledby="user-results-title">
        <h2 id="user-results-title">User results</h2>
        <table>
            <caption>{{ $users->total() }} users, ordered by name and account ID</caption>
            <thead>
            <tr>
                <th scope="col">Account</th>
                <th scope="col">Identity</th>
                <th scope="col">Status</th>
                <th scope="col">Protected role</th>
                <th scope="col">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td><strong>{{ $user->name }}</strong><br><span class="muted">{{ $user->email }}</span></td>
                    <td>{{ $identityTypes[$user->identity_type] ?? 'Unknown' }}</td>
                    <td>{{ $user->suspended_at === null ? 'Active' : 'Suspended' }}</td>
                    <td>{{ $authorizationProfile->effectiveProtectedRole($user) ?? 'None' }}</td>
                    <td><a href="{{ route('admin.users.show', $user) }}">View user</a></td>
                </tr>
            @empty
                <tr><td colspan="5">No users match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $users->links() }}
    </section>
@endsection
