<nav class="admin-nav" aria-label="Admin sections">
    @can('admin.analytics.view')
        <a href="{{ route('admin.index') }}" @if(request()->routeIs('admin.index')) aria-current="page" @endif>Overview</a>
    @endcan
    @can('admin.users.view')
        <a href="{{ route('admin.users.index', ['account_type' => 'public']) }}" @if(request()->routeIs('admin.users.*') && request('account_type') === 'public') aria-current="page" @endif>Public users</a>
        <a href="{{ route('admin.users.index', ['account_type' => 'staff']) }}" @if(request()->routeIs('admin.users.*') && request('account_type') === 'staff') aria-current="page" @endif>Staff</a>
    @endcan
    @canany(['admin.groups.view', 'admin.roles.view', 'admin.permissions.view'])
        <a href="{{ route('admin.access.index') }}" @if(request()->routeIs('admin.access.*', 'admin.groups.*', 'admin.roles.*', 'admin.permissions.*')) aria-current="page" @endif>Access</a>
    @endcanany
    @can('admin.modules.view')
        <a href="{{ route('admin.modules.index') }}" @if(request()->routeIs('admin.modules.*')) aria-current="page" @endif>Plugins</a>
    @endcan
    @can('admin.maintenance.manage')
        <a href="{{ route('admin.maintenance.index') }}" @if(request()->routeIs('admin.maintenance.*')) aria-current="page" @endif>Maintenance</a>
    @endcan
</nav>
