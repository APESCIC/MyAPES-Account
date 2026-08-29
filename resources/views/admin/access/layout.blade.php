@extends('layouts.app')

@section('title', 'Access | MyAPES Core')

@section('content')
    @include('superadmin._navigation')

    <section class="panel" aria-labelledby="admin-access-title">
        <h1 id="admin-access-title">Access</h1>
        <p class="muted">Manage Cloudron group mappings, job-role capability packs, and the permission catalogue.</p>

        <nav class="admin-subnav" aria-label="Access sections">
            @can('admin.groups.view')
                <a href="{{ route('admin.access.index', ['tab' => 'groups']) }}"
                   @if(($activeTab ?? 'groups') === 'groups') aria-current="page" @endif>Groups</a>
            @endcan
            @can('admin.roles.view')
                <a href="{{ route('admin.access.index', ['tab' => 'job-roles']) }}"
                   @if(($activeTab ?? '') === 'job-roles') aria-current="page" @endif>Job roles</a>
            @endcan
            @can('admin.permissions.view')
                <a href="{{ route('admin.access.index', ['tab' => 'permissions']) }}"
                   @if(($activeTab ?? '') === 'permissions') aria-current="page" @endif>Permission catalogue</a>
            @endcan
        </nav>
    </section>

    @yield('access-content')
@endsection
