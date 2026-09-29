@extends('admin.access.layout')

@section('access-content')
    <section class="panel" aria-labelledby="access-permissions-title">
        <h2 id="access-permissions-title">{{ __('admin.access.permission_catalogue') }}</h2>
        <p class="muted">{{ __('admin.access.permissions_intro') }}</p>
        <form method="get" action="{{ route('admin.access.index') }}" class="permission-filter-form">
            <input type="hidden" name="tab" value="permissions">
            <div class="row">
                <div>
                    <label for="permission-search">{{ __('admin.access.search') }}</label>
                    <input id="permission-search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="100" placeholder="{{ __('admin.access.title_or_key_e_g_users_or_admin_modules') }}">
                </div>
                <div>
                    <label for="permission-layer">{{ __('admin.access.layer') }}</label>
                    <select id="permission-layer" name="layer">
                        <option value="">{{ __('admin.access.all_layers') }}</option>
                        @foreach($layers as $layer)
                            <option value="{{ $layer }}" @selected(($filters['layer'] ?? '') === $layer)>{{ $layer }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="permission-group">{{ __('admin.access.group') }}</label>
                    <select id="permission-group" name="group">
                        <option value="">{{ __('admin.access.all_groups') }}</option>
                        @foreach($groups as $group)
                            <option value="{{ $group }}" @selected(($filters['group'] ?? '') === $group)>{{ $group }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="actions">
                <button type="submit">{{ __('admin.access.apply_filters') }}</button>
                <a href="{{ route('admin.access.index', ['tab' => 'permissions']) }}">{{ __('admin.access.clear') }}</a>
            </div>
        </form>
    </section>

    <section class="panel" aria-labelledby="permission-results-title">
        <h2 id="permission-results-title">{{ __('admin.access.catalogue_results') }}</h2>
        <p class="muted">{{ $permissions->total() }} permissions match these filters.</p>

        @php
            $grouped = $permissions->getCollection()
                ->groupBy(fn ($permission) => \App\Support\PermissionDescriptions::layer($permission->name));
            $layerOrder = ['Core' => 0, 'Module' => 1, 'Plugin' => 2];
            $grouped = $grouped->sortBy(fn ($items, $layer) => $layerOrder[$layer] ?? 99);
        @endphp

        @forelse($grouped as $layerName => $layerPermissions)
            <div class="permission-catalogue-group" data-permission-layer="{{ $layerName }}">
                <h3>{{ $layerName }}</h3>
                <ul class="permission-readable-list">
                    @foreach($layerPermissions as $permission)
                        <li>
                            <div class="permission-readable-heading">
                                <strong>{{ \App\Support\PermissionDescriptions::title($permission->name) }}</strong>
                                <code>{{ $permission->name }}</code>
                            </div>
                            <p class="muted">{{ \App\Support\PermissionDescriptions::description($permission->name) }}</p>
                            <p class="muted">Group: {{ \App\Support\PermissionDescriptions::group($permission->name) }}</p>
                            <p>
                                <span class="muted">{{ __('admin.access.assigned_job_roles') }}</span>
                                @forelse($permission->roles as $role)
                                    <a href="{{ route('admin.access.job-roles.show', $role) }}">{{ \App\Support\DefaultJobRoles::isDefault($role->name) ? \App\Support\DefaultJobRoles::title($role->name) : $role->name }}</a>@if(! $loop->last), @endif
                                @empty
                                    {{ __('admin.access.none') }}
                                @endforelse
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <p>{{ __('admin.access.no_permissions_match_this_search') }}</p>
        @endforelse

        {{ $permissions->appends(['tab' => 'permissions'])->links() }}
    </section>
@endsection
