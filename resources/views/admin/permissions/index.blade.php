@extends('layouts.app')

@section('title', 'Admin permissions | MyAPES Account')

@section('content')
    @include('admin._navigation')

    <section class="panel" aria-labelledby="admin-permissions-title">
        <h1 id="admin-permissions-title">Permissions</h1>
        <p class="muted">Code-owned permission catalogue. Permissions are read-only and can be assigned only through custom roles.</p>
        <form method="get" action="{{ route('admin.permissions.index') }}">
            <label for="permission-search">Search permission keys</label>
            <input id="permission-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100">
            <div class="actions">
                <button type="submit">Search</button>
                <a href="{{ route('admin.permissions.index') }}">Clear search</a>
            </div>
        </form>
    </section>

    <section class="panel" aria-labelledby="permission-results-title">
        <h2 id="permission-results-title">Permission catalogue</h2>
        <table>
            <caption>{{ $permissions->total() }} code-owned permissions, ordered by key</caption>
            <thead><tr><th scope="col">Key</th><th scope="col">Description</th><th scope="col">Owner</th><th scope="col">Assigned roles</th></tr></thead>
            <tbody>
            @forelse($permissions as $permission)
                <tr>
                    <td><code>{{ $permission->name }}</code></td>
                    <td>{{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $permission->name)) }}</td>
                    <td>Application code</td>
                    <td>
                        @forelse($permission->roles as $role)
                            <a href="{{ route('admin.roles.show', $role) }}">{{ $role->name }}</a>@if(! $loop->last), @endif
                        @empty
                            None
                        @endforelse
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">No permissions match this search.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $permissions->links() }}
    </section>
@endsection
