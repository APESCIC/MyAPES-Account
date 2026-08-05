@extends('layouts.app')

@section('title', 'Admin user | MyAPES Account')

@section('content')
    @include('admin._navigation')

    <section class="panel" aria-labelledby="managed-user-title">
        <p><a href="{{ route('admin.users.index') }}">← Back to users</a></p>
        <h1 id="managed-user-title">{{ $managedUser->name }}</h1>
        <p class="muted">{{ $managedUser->email }}</p>
        <dl class="admin-definition-list">
            <div><dt>Account ID</dt><dd>{{ $managedUser->id }}</dd></div>
            <div><dt>Identity source</dt><dd>{{ $identityLabel }}</dd></div>
            <div><dt>Suspension state</dt><dd>{{ $managedUser->suspended_at === null ? 'Active' : 'Suspended' }}</dd></div>
            <div><dt>Authorization epoch</dt><dd>{{ $managedUser->authorization_epoch }}</dd></div>
            <div>
                <dt>Normalized directory groups</dt>
                <dd>
                    @forelse(($managedUser->ldap_groups ?? []) as $group)
                        <code>{{ $group }}</code>@if(! $loop->last), @endif
                    @empty
                        None recorded
                    @endforelse
                </dd>
            </div>
        </dl>
    </section>

    <section class="panel" aria-labelledby="role-provenance-title">
        <h2 id="role-provenance-title">Provenanced roles</h2>
        <table>
            <caption>Effective role assignments and their recorded sources</caption>
            <thead><tr><th scope="col">Role</th><th scope="col">Source</th><th scope="col">Directory group</th></tr></thead>
            <tbody>
            @forelse($managedUser->roleSources as $source)
                <tr>
                    <td>{{ $source->getRelation('role')->name }}</td>
                    <td>{{ $source->source }}</td>
                    <td>{{ $source->directoryGroup?->name ?? 'Not applicable' }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No role provenance is recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>

    <section class="panel" aria-labelledby="effective-permissions-title">
        <h2 id="effective-permissions-title">Effective permissions</h2>
        <ul>
            @forelse($permissions as $permission)
                <li><code>{{ $permission->name }}</code></li>
            @empty
                <li>No effective permissions.</li>
            @endforelse
        </ul>
    </section>

    <section class="panel" aria-labelledby="direct-permission-provenance-title">
        <h2 id="direct-permission-provenance-title">Direct permission provenance</h2>
        <table>
            <caption>Direct permissions and their recorded assignment sources</caption>
            <thead><tr><th scope="col">Permission</th><th scope="col">Source</th><th scope="col">Granting account</th></tr></thead>
            <tbody>
            @forelse($managedUser->permissionSources as $source)
                <tr>
                    <td><code>{{ $source->getRelation('permission')->name }}</code></td>
                    <td>{{ $source->source }}</td>
                    <td>
                        @if($source->source === \App\Models\PermissionSource::SOURCE_SYSTEM && $source->actor === null)
                            System
                        @elseif($source->actor !== null)
                            Account {{ $source->actor->id }}
                        @else
                            Unavailable
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="3">No direct permission provenance is recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>

    @can('admin.users.manage')
        @if($canManageTarget)
            <section class="panel" aria-labelledby="manage-user-title">
                <h2 id="manage-user-title">Manage user</h2>
                <p class="muted">Only custom local roles can be changed. Protected and directory-derived assignments remain read-only.</p>

                <form method="post" action="{{ route('admin.users.roles.update', $managedUser) }}">
                    @csrf
                    @method('put')
                    <fieldset class="admin-checkbox-grid">
                        <legend>Custom local roles</legend>
                        @forelse($customRoles as $role)
                            <label class="inline-check">
                                <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id, $localRoleIds, true))>
                                <span>{{ $role->name }}</span>
                            </label>
                        @empty
                            <p>No custom roles are available.</p>
                        @endforelse
                    </fieldset>
                    <div class="actions"><button type="submit">Update local roles</button></div>
                </form>

                <hr class="section-divider">

                @if($managedUser->suspended_at === null)
                    <form method="post" action="{{ route('admin.users.suspension.store', $managedUser) }}">
                        @csrf
                        <label for="suspension-reason">Suspension reason</label>
                        <textarea id="suspension-reason" name="reason" required maxlength="500"></textarea>
                        <div class="actions"><button class="danger-btn" type="submit">Suspend user</button></div>
                    </form>
                @else
                    <form method="post" action="{{ route('admin.users.suspension.destroy', $managedUser) }}">
                        @csrf
                        @method('delete')
                        <button type="submit">Reactivate user</button>
                    </form>
                @endif
            </section>
        @endif
    @endcan

    <section class="panel" aria-labelledby="audit-history-title">
        <h2 id="audit-history-title">Sanitized audit history</h2>
        <table>
            <caption>Recent authorization events; sensitive identity and request payload data is excluded</caption>
            <thead><tr><th scope="col">Time</th><th scope="col">Event</th><th scope="col">Actor ID</th><th scope="col">Safe context</th></tr></thead>
            <tbody>
            @forelse($auditHistory as $audit)
                <tr>
                    <td>{{ $audit['created_at'] }}</td>
                    <td><code>{{ $audit['event'] }}</code></td>
                    <td>{{ $audit['actor_id'] ?? 'System' }}</td>
                    <td>
                        @forelse($audit['context'] as $key => $value)
                            <div><code>{{ $key }}</code>: {{ is_array($value) ? implode(', ', $value) : $value }}</div>
                        @empty
                            No displayable context
                        @endforelse
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">No audit history is recorded for this user.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endsection
