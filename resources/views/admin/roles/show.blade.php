@extends('layouts.app')

@section('title', 'Admin role | MyAPES Account')

@section('content')
    @include('admin._navigation')

    <section class="panel" aria-labelledby="managed-role-title">
        <p><a href="{{ route('admin.roles.index') }}">← Back to roles</a></p>
        <h1 id="managed-role-title">{{ $managedRole->name }}</h1>
        <dl class="admin-definition-list">
            <div><dt>Role ID</dt><dd>{{ $managedRole->id }}</dd></div>
            <div><dt>Ownership</dt><dd>{{ $managedRole->is_protected ? 'Protected by application code' : 'Custom' }}</dd></div>
            <div><dt>Assigned users</dt><dd>{{ $managedRole->users_count }}</dd></div>
            <div><dt>Permissions</dt><dd>{{ $managedRole->permissions_count }}</dd></div>
        </dl>
    </section>

    <section class="panel" aria-labelledby="role-permissions-title">
        <h2 id="role-permissions-title">Permission matrix</h2>
        @if($managedRole->is_protected)
            <p class="muted">This protected matrix is read-only and synchronized from application code.</p>
        @else
            <p class="muted">Current permissions are read-only unless you have custom-role management authorization.</p>
        @endif
        <ul>
            @forelse($managedRole->permissions as $permission)
                <li><code>{{ $permission->name }}</code></li>
            @empty
                <li>No permissions.</li>
            @endforelse
        </ul>

        @if(! $managedRole->is_protected && \Illuminate\Support\Facades\Gate::denies('admin.roles.manage'))
            <p class="muted">Custom role management requires super-admin authorization.</p>
        @endif

        @can('admin.roles.manage')
            @if(! $managedRole->is_protected)
                <form method="post" action="{{ route('admin.roles.update', $managedRole) }}">
                    @csrf
                    @method('put')
                    <label for="role-name">Role name</label>
                    <input id="role-name" name="name" value="{{ $managedRole->name }}" required minlength="3" maxlength="64" pattern="[a-z][a-z0-9]*(?:-[a-z0-9]+)*">
                    <fieldset class="admin-checkbox-grid">
                        <legend>Code-owned permissions</legend>
                        @foreach($permissions as $permission)
                            <label class="inline-check">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked($managedRole->permissions->contains($permission))>
                                <code>{{ $permission->name }}</code>
                            </label>
                        @endforeach
                    </fieldset>
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
