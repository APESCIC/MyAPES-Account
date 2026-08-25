@extends('admin.access.layout')

@section('access-content')
    <section class="panel" aria-labelledby="access-permissions-title">
        <h2 id="access-permissions-title">Permission catalogue</h2>
        <p class="muted">Code-owned catalogue with plain-language titles. Permissions are assigned through job roles, not directly to users.</p>
        <form method="get" action="{{ route('admin.access.index') }}" class="permission-filter-form">
            <input type="hidden" name="tab" value="permissions">
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
                <a href="{{ route('admin.access.index', ['tab' => 'permissions']) }}">Clear</a>
            </div>
        </form>
    </section>

    <section class="panel" aria-labelledby="permission-results-title">
        <h2 id="permission-results-title">Catalogue results</h2>
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
                                <span class="muted">Assigned job roles:</span>
                                @forelse($permission->roles as $role)
                                    <a href="{{ route('admin.access.job-roles.show', $role) }}">{{ \App\Support\DefaultJobRoles::isDefault($role->name) ? \App\Support\DefaultJobRoles::title($role->name) : $role->name }}</a>@if(! $loop->last), @endif
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

        {{ $permissions->appends(['tab' => 'permissions'])->links() }}
    </section>
@endsection
