@extends('layouts.app')

@section('title', 'Admin roles | MyAPES Account')

@section('content')
    @include('admin._navigation')

    <section class="panel" aria-labelledby="admin-roles-title">
        <h1 id="admin-roles-title">Roles</h1>
        <p class="muted">Protected role matrices are read-only. Super-admins can create custom roles using only code-owned catalogue permissions.</p>

        <form method="get" action="{{ route('admin.roles.index') }}">
            <label for="role-search">Search roles</label>
            <input id="role-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100">
            <div class="actions">
                <button type="submit">Search</button>
                <a href="{{ route('admin.roles.index') }}">Clear search</a>
            </div>
        </form>
    </section>

    @can('admin.roles.manage')
        <section class="panel" aria-labelledby="create-role-title">
            <h2 id="create-role-title">Create custom role</h2>
            <form method="post" action="{{ route('admin.roles.store') }}">
                @csrf
                <label for="new-role-name">Role name</label>
                <input id="new-role-name" name="name" required minlength="3" maxlength="64" pattern="[a-z][a-z0-9]*(?:-[a-z0-9]+)*" aria-describedby="role-name-help">
                <p id="role-name-help" class="muted">Use lower kebab case, for example <code>case-reviewer</code>.</p>
                <fieldset class="admin-checkbox-grid">
                    <legend>Permissions</legend>
                    @foreach($permissions as $permission)
                        <label class="inline-check">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}">
                            <code>{{ $permission->name }}</code>
                        </label>
                    @endforeach
                </fieldset>
                <div class="actions"><button type="submit">Create role</button></div>
            </form>
        </section>
    @endcan

    <section class="panel" aria-labelledby="role-results-title">
        <h2 id="role-results-title">Role results</h2>
        <table>
            <caption>{{ $roles->total() }} roles, ordered by protection state and name</caption>
            <thead><tr><th scope="col">Role</th><th scope="col">Ownership</th><th scope="col">Permissions</th><th scope="col">Assigned users</th><th scope="col">Action</th></tr></thead>
            <tbody>
            @forelse($roles as $role)
                <tr>
                    <td><code>{{ $role->name }}</code></td>
                    <td>{{ $role->is_protected ? 'Protected by application code' : 'Custom' }}</td>
                    <td>{{ $role->permissions_count }}</td>
                    <td>{{ $role->users_count }}</td>
                    <td><a href="{{ route('admin.roles.show', $role) }}">View role</a></td>
                </tr>
            @empty
                <tr><td colspan="5">No roles match this search.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $roles->links() }}
    </section>
@endsection
