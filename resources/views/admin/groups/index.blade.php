@extends('layouts.app')

@section('title', 'Super Admin groups | MyAPES Core')

@section('content')
    @include('superadmin._navigation')

    <section class="panel" aria-labelledby="admin-groups-title">
        <h1 id="admin-groups-title">Super Admin groups</h1>
        <p class="muted">Preset Cloudron <code>myapesaccount.*</code> groups used for MyAPES Account authorization. Directory sync imports only these groups and provisions staff profiles from LDAP membership.</p>

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
        <h2 id="group-results-title">Preset groups</h2>
        <table>
            <caption>{{ $groups->total() }} preset directory groups, ordered by normalized name</caption>
            <thead>
                <tr>
                    <th scope="col">Group</th>
                    <th scope="col">Source</th>
                    <th scope="col">Status</th>
                    <th scope="col">Members</th>
                    <th scope="col">Mapped role</th>
                </tr>
            </thead>
            <tbody>
            @forelse($groups as $group)
                <tr>
                    <td><code>{{ $group->name }}</code></td>
                    <td>
                        <span class="status" title="{{ \App\Support\DirectoryGroupLabels::sourceHint($group) }}">
                            {{ \App\Support\DirectoryGroupLabels::sourceLabel($group) }}
                        </span>
                    </td>
                    <td>{{ ucfirst($group->status) }}</td>
                    <td>{{ $group->member_count ?? 'Unknown' }}</td>
                    <td>
                        @forelse($group->roles as $role)
                            <div>
                                <a href="{{ route('admin.roles.show', $role) }}">{{ $role->name }}</a>
                                <span class="status">{{ \App\Support\DirectoryGroupLabels::mappingLabel((bool) $role->pivot->is_immutable) }}</span>
                            </div>
                        @empty
                            None
                        @endforelse
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">No preset directory groups match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $groups->links() }}
    </section>
@endsection
