@extends('layouts.app')

@section('title', 'Super Admin role | MyAPES Core')

@section('content')
    @include('superadmin._navigation')

    <section class="panel" aria-labelledby="managed-role-title">
        <p><a href="{{ route('admin.roles.index') }}">← Back to Super Admin roles</a></p>
        <h1 id="managed-role-title">{{ $managedRole->name }}</h1>
        <dl class="admin-definition-list">
            <div><dt>Ownership</dt><dd>{{ $managedRole->is_protected ? 'Protected by application code' : 'Custom' }}</dd></div>
            <div><dt>Assigned users</dt><dd>{{ $managedRole->users_count }}</dd></div>
            <div><dt>Permissions</dt><dd>{{ $managedRole->permissions_count }}</dd></div>
        </dl>
    </section>

    <section class="panel" aria-labelledby="role-permissions-title">
        <h2 id="role-permissions-title">Permissions</h2>
        @if($managedRole->is_protected)
            <p class="muted">This protected set is read-only and synchronized from application code.</p>
        @else
            <p class="muted">Current permissions are read-only unless you have custom-role management authorization.</p>
        @endif

        @php
            $assignedGroups = $managedRole->permissions
                ->groupBy(fn ($permission) => \App\Support\PermissionDescriptions::group($permission->name))
                ->sortKeys();
        @endphp
        @forelse($assignedGroups as $groupName => $groupPermissions)
            <div class="permission-readable-group">
                <h3>{{ $groupName }}</h3>
                <ul class="permission-readable-list">
                    @foreach($groupPermissions as $permission)
                        <li>
                            <strong>{{ \App\Support\PermissionDescriptions::title($permission->name) }}</strong>
                            <p class="muted">{{ \App\Support\PermissionDescriptions::description($permission->name) }}</p>
                            <code>{{ $permission->name }}</code>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <p>No permissions.</p>
        @endforelse

        @if(! $managedRole->is_protected && \Illuminate\Support\Facades\Gate::denies('admin.roles.manage'))
            <p class="muted">Custom role management requires Super Admin authorization.</p>
        @endif

        @can('admin.roles.manage')
            @if(! $managedRole->is_protected)
                <form method="post" action="{{ route('admin.roles.update', $managedRole) }}">
                    @csrf
                    @method('put')
                    <label for="role-name">Role name</label>
                    <input id="role-name" name="name" value="{{ $managedRole->name }}" required minlength="3" maxlength="64" pattern="[a-z][a-z0-9]*(?:-[a-z0-9]+)*">
                    @php
                        $editGroups = $permissions
                            ->groupBy(fn ($permission) => \App\Support\PermissionDescriptions::group($permission->name))
                            ->sortKeys();
                    @endphp
                    @foreach($editGroups as $groupName => $groupPermissions)
                        <fieldset class="permission-choice-group">
                            <legend>{{ $groupName }}</legend>
                            <ul class="permission-choice-list">
                                @foreach($groupPermissions as $permission)
                                    <li>
                                        <label class="permission-choice">
                                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked($managedRole->permissions->contains($permission))>
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
                    <div class="actions"><button type="submit">Update role</button></div>
                </form>

                <form class="stack-spaced" method="post" action="{{ route('admin.roles.destroy', $managedRole) }}">
                    @csrf
                    @method('delete')
                    <button class="danger-btn" type="submit">Delete custom role</button>
                </form>
            @endif
        @endcan
    </section>
@endsection
