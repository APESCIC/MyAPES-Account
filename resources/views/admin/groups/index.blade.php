@extends('layouts.app')

@section('title', 'Admin groups | MyAPES Account')

@section('content')
    @include('admin._navigation')

    <section class="panel" aria-labelledby="admin-groups-title">
        <h1 id="admin-groups-title">Directory groups</h1>
        <p class="muted">Review the normalized directory catalogue and its role mappings. Missing groups remain visible for operational review.</p>

        <form method="get" action="{{ route('admin.groups.index') }}">
            <div class="row">
                <div>
                    <label for="group-search">Search group name</label>
                    <input id="group-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100">
                </div>
                <div>
                    <label for="group-status">Catalogue status</label>
                    <select id="group-status" name="status">
                        <option value="">All statuses</option>
                        <option value="present" @selected(($filters['status'] ?? '') === 'present')>Present</option>
                        <option value="missing" @selected(($filters['status'] ?? '') === 'missing')>Missing</option>
                    </select>
                </div>
                <div>
                    <label for="group-mapped">Mapping status</label>
                    <select id="group-mapped" name="mapped">
                        <option value="">All groups</option>
                        <option value="1" @selected(($filters['mapped'] ?? '') === '1')>Mapped</option>
                        <option value="0" @selected(($filters['mapped'] ?? '') === '0')>Unmapped</option>
                    </select>
                </div>
            </div>
            <div class="actions">
                <button type="submit">Apply filters</button>
                <a href="{{ route('admin.groups.index') }}">Clear filters</a>
            </div>
        </form>

        @can('admin.group-mappings.manage')
            <form class="stack-spaced" method="post" action="{{ route('admin.groups.sync') }}">
                @csrf
                <button type="submit">Queue manual directory synchronization</button>
            </form>
        @endcan
    </section>

    <section class="panel" aria-labelledby="group-results-title">
        <h2 id="group-results-title">Group results</h2>
        <table>
            <caption>{{ $groups->total() }} directory groups, ordered by normalized name</caption>
            <thead><tr><th scope="col">Group</th><th scope="col">Status</th><th scope="col">Members</th><th scope="col">Mapped roles</th><th scope="col">Management</th></tr></thead>
            <tbody>
            @forelse($groups as $group)
                <tr>
                    <td><code>{{ $group->name }}</code></td>
                    <td>{{ ucfirst($group->status) }}</td>
                    <td>{{ $group->member_count ?? 'Unknown' }}</td>
                    <td>
                        @forelse($group->roles as $role)
                            <div>
                                <a href="{{ route('admin.roles.show', $role) }}">{{ $role->name }}</a>
                                @if($role->pivot->is_immutable)
                                    <span class="status">Protected mapping</span>
                                @endif
                                @can('admin.group-mappings.manage')
                                    @if(! $role->pivot->is_immutable)
                                        <form class="inline" method="post" action="{{ route('admin.groups.mappings.destroy', $role->pivot->id) }}">
                                            @csrf
                                            @method('delete')
                                            <button class="danger-btn" type="submit">Remove</button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        @empty
                            None
                        @endforelse
                    </td>
                    <td>
                        @can('admin.group-mappings.manage')
                            <form method="post" action="{{ route('admin.groups.mappings.store', $group) }}">
                                @csrf
                                <label for="mapping-role-{{ $group->id }}">Add role mapping</label>
                                <select id="mapping-role-{{ $group->id }}" name="role_id" required>
                                    <option value="">Choose a role</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                                <button type="submit">Add mapping</button>
                            </form>
                        @else
                            Read-only
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">No directory groups match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $groups->links() }}
    </section>
@endsection
