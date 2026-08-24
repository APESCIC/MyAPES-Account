@extends('layouts.app')

@section('title', 'Super Admin permissions | MyAPES Core')

@section('content')
    @include('superadmin._navigation')

    <section class="panel" aria-labelledby="admin-permissions-title">
        <h1 id="admin-permissions-title">Super Admin permissions</h1>
        <p class="muted">Code-owned catalogue with plain-language titles. Permissions are assigned through custom roles, not directly to users.</p>
        <form method="get" action="{{ route('admin.permissions.index') }}" class="permission-filter-form">
            <div class="row">
                <div>
                    <label for="permission-search">Search</label>
                    <input id="permission-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="Title or key, e.g. users or admin.modules">
                </div>
                <div>
                    <label for="permission-group">Group</label>
                    <select id="permission-group" name="group">
                        <option value="">All groups</option>
                        @foreach($groups as $group)
                            <option value="{{ $group }}" @selected(($filters['group'] ?? '') === $group)>{{ $group }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="actions">
                <button type="submit">Apply filters</button>
                <a href="{{ route('admin.permissions.index') }}">Clear</a>
            </div>
        </form>
    </section>

    <section class="panel" aria-labelledby="permission-results-title">
        <h2 id="permission-results-title">Permission catalogue</h2>
        <p class="muted">{{ $permissions->total() }} permissions match these filters.</p>

        @php
            $grouped = $permissions->getCollection()
                ->groupBy(fn ($permission) => \App\Support\PermissionDescriptions::group($permission->name))
                ->sortKeys();
        @endphp

        @forelse($grouped as $groupName => $groupPermissions)
            <div class="permission-catalogue-group">
                <h3>{{ $groupName }}</h3>
                <ul class="permission-readable-list">
                    @foreach($groupPermissions as $permission)
                        <li>
                            <div class="permission-readable-heading">
                                <strong>{{ \App\Support\PermissionDescriptions::title($permission->name) }}</strong>
                                <code>{{ $permission->name }}</code>
                            </div>
                            <p class="muted">{{ \App\Support\PermissionDescriptions::description($permission->name) }}</p>
                            <p>
                                <span class="muted">Assigned roles:</span>
                                @forelse($permission->roles as $role)
                                    <a href="{{ route('admin.roles.show', $role) }}">{{ $role->name }}</a>@if(! $loop->last), @endif
                                @empty
                                    None
                                @endforelse
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <p>No permissions match this search.</p>
        @endforelse

        {{ $permissions->links() }}
    </section>
@endsection
