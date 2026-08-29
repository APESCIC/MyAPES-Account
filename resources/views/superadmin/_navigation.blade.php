<nav class="admin-nav" aria-label="Super Admin sections">
    @can('superadmin.access')
        <a href="{{ route('superadmin.index') }}" @if(request()->routeIs('superadmin.index')) aria-current="page" @endif>Overview</a>
    @endcan
    @canany(['admin.groups.view', 'admin.roles.view', 'admin.permissions.view'])
        <a href="{{ route('admin.access.index') }}" @if(request()->routeIs('admin.access.*')) aria-current="page" @endif>Access</a>
    @endcanany
    @can('admin.modules.view')
        <a href="{{ route('admin.modules.index') }}" @if(request()->routeIs('admin.modules.*')) aria-current="page" @endif>Plugins</a>
    @endcan
    @can('admin.maintenance.manage')
        <a href="{{ route('admin.maintenance.index') }}" @if(request()->routeIs('admin.maintenance.*')) aria-current="page" @endif>Maintenance</a>
    @endcan
</nav>
