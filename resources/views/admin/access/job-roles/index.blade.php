@extends('admin.access.layout')

@section('access-content')
    <section class="panel" aria-labelledby="access-job-roles-title">
        <h2 id="access-job-roles-title">Job roles</h2>
        <p class="muted">Default and custom job roles use reviewed capability packs. Protected access tiers are managed in application code.</p>

        <form method="get" action="{{ route('admin.access.index') }}">
            <input type="hidden" name="tab" value="job-roles">
            <label for="role-search">Search job roles</label>
            <input id="role-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100">
            <div class="actions">
                <button type="submit">Search</button>
                <a href="{{ route('admin.access.index', ['tab' => 'job-roles']) }}">Clear search</a>
            </div>
        </form>
    </section>

    @can('admin.roles.manage')
        <section class="panel" aria-labelledby="create-job-role-title">
            <h2 id="create-job-role-title">Create job role</h2>
            <form method="post" action="{{ route('admin.access.job-roles.store') }}">
                @csrf
                <label for="new-role-name">Role name</label>
                <input id="new-role-name" name="name" required minlength="3" maxlength="64" pattern="[a-z][a-z0-9]*(?:-[a-z0-9]+)*" aria-describedby="role-name-help">
                <p id="role-name-help" class="muted">Use lower kebab case, for example <code>case-reviewer</code>.</p>

                <fieldset class="permission-choice-group">
                    <legend>Capability packs</legend>
                    <ul class="permission-choice-list">
                        @foreach($packDefinitions as $packKey => $pack)
                            <li>
                                <label class="permission-choice">
                                    <input type="checkbox"
                                           data-pack
                                           data-pack-permissions='@json($pack['permissions'])'
                                           @disabled($pack['permissions'] === [])>
                                    <span class="permission-choice__body">
                                        <span class="permission-choice__title">{{ $pack['title'] }}</span>
                                        <span class="permission-choice__description muted">{{ count($pack['permissions']) }} permissions</span>
                                    </span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </fieldset>

                <details class="permission-advanced">
                    <summary>Advanced permissions</summary>
                    @php
                        $createGroups = $permissions
                            ->groupBy(fn ($permission) => \App\Support\PermissionDescriptions::group($permission->name))
                            ->sortKeys();
                    @endphp
                    @foreach($createGroups as $groupName => $groupPermissions)
                        <fieldset class="permission-choice-group">
                            <legend>{{ $groupName }}</legend>
                            <ul class="permission-choice-list">
                                @foreach($groupPermissions as $permission)
                                    <li>
                                        <label class="permission-choice">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}">
                                            <span class="permission-choice__body">
                                                <span class="permission-choice__title">{{ \App\Support\PermissionDescriptions::title($permission->name) }}</span>
                                                <span class="permission-choice__description muted">{{ \App\Support\PermissionDescriptions::description($permission->name) }}</span>
                                                <code class="permission-choice__key">{{ $permission->name }}</code>
                                            </span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </fieldset>
                    @endforeach
                </details>

                <div class="actions"><button type="submit">Create job role</button></div>
            </form>
        </section>
    @endcan

    <section class="panel" aria-labelledby="job-role-results-title">
        <h2 id="job-role-results-title">Job role results</h2>
        <table>
            <caption>{{ $roles->total() }} job roles, ordered by name</caption>
            <thead><tr><th scope="col">Role</th><th scope="col">Ownership</th><th scope="col">Permissions</th><th scope="col">Assigned users</th><th scope="col">Action</th></tr></thead>
            <tbody>
            @forelse($roles as $role)
                <tr>
                    <td>
                        @if(\App\Support\DefaultJobRoles::isDefault($role->name))
                            {{ \App\Support\DefaultJobRoles::title($role->name) }}
                            <code>{{ $role->name }}</code>
                        @else
                            <code>{{ $role->name }}</code>
                        @endif
                    </td>
                    <td>{{ \App\Support\DefaultJobRoles::isDefault($role->name) ? 'Default job role' : 'Custom' }}</td>
                    <td>{{ $role->permissions_count }}</td>
                    <td>{{ $role->users_count }}</td>
                    <td><a href="{{ route('admin.access.job-roles.show', $role) }}">View role</a></td>
                </tr>
            @empty
                <tr><td colspan="5">No job roles match this search.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $roles->appends(['tab' => 'job-roles'])->links() }}
    </section>

    @include('admin.access.partials.pack-script')
@endsection
