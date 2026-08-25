@extends('admin.access.layout')

@section('access-content')
    <section class="panel" aria-labelledby="access-groups-title">
        <h2 id="access-groups-title">Groups</h2>
        <p class="muted">Managed Cloudron <code>myapesaccount.*</code> groups used for MyAPES Account authorization. Directory sync imports only these groups (legacy aliases map to the canonical names). Historical non-prefix catalogue rows stay in the database for audits but are hidden here. Protected access-tier mappings stay preset; Super Admins can attach an optional job role.</p>

        <form method="get" action="{{ route('admin.access.index') }}">
            <input type="hidden" name="tab" value="groups">
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
                <a href="{{ route('admin.access.index', ['tab' => 'groups']) }}">Clear filters</a>
            </div>
        </form>

        @can('admin.group-mappings.manage')
            <form class="stack-spaced" method="post" action="{{ route('admin.access.sync') }}">
                @csrf
                <button type="submit">Sync from Cloudron</button>
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
                    <th scope="col">Status</th>
                    <th scope="col">Access tier</th>
                    <th scope="col">Optional job role</th>
                </tr>
            </thead>
            <tbody>
            @forelse($groups as $group)
                @php
                    $accessTier = $group->roles->first(fn ($role) => (bool) $role->pivot->is_immutable);
                    $jobRoleMappings = $group->roles->filter(fn ($role) => ! (bool) $role->pivot->is_immutable);
                @endphp
                <tr>
                    <td><code>{{ $group->name }}</code></td>
                    <td>{{ ucfirst($group->status) }}</td>
                    <td>
                        @if($accessTier)
                            <code>{{ $accessTier->name }}</code>
                            <span class="status">{{ \App\Support\DirectoryGroupLabels::mappingLabel(true) }}</span>
                        @else
                            None
                        @endif
                    </td>
                    <td>
                        @forelse($jobRoleMappings as $role)
                            <div>
                                <a href="{{ route('admin.access.job-roles.show', $role) }}">{{ \App\Support\DefaultJobRoles::isDefault($role->name) ? \App\Support\DefaultJobRoles::title($role->name) : $role->name }}</a>
                                @can('admin.group-mappings.manage')
                                    <form class="inline" method="post" action="{{ route('admin.access.mappings.destroy', $role->pivot->id) }}">
                                        @csrf
                                        @method('delete')
                                        <button class="danger-btn" type="submit">Remove job role</button>
                                    </form>
                                @endcan
                            </div>
                        @empty
                            None
                        @endforelse
                        @can('admin.group-mappings.manage')
                            <form method="post" action="{{ route('admin.access.mappings.store', $group) }}">
                                @csrf
                                <label for="mapping-role-{{ $group->id }}">Add job role mapping</label>
                                <select id="mapping-role-{{ $group->id }}" name="role_id" required>
                                    <option value="">Choose a job role</option>
                                    @foreach($jobRoles as $jobRole)
                                        <option value="{{ $jobRole->id }}">{{ \App\Support\DefaultJobRoles::title($jobRole->name) }}</option>
                                    @endforeach
                                </select>
                                <button type="submit">Add mapping</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">No preset directory groups match these filters.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $groups->appends(['tab' => 'groups'])->links() }}
    </section>
@endsection
