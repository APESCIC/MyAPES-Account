<nav class="admin-nav" aria-label="Admin sections">
    <a href="{{ route('admin.index') }}" @if(request()->routeIs('admin.index')) aria-current="page" @endif>Overview</a>
    @can('admin.users.view')
        <a href="{{ route('admin.users.index') }}" @if(request()->routeIs('admin.users.*')) aria-current="page" @endif>Users</a>
    @endcan
    @can('admin.groups.view')
        <a href="{{ route('admin.groups.index') }}" @if(request()->routeIs('admin.groups.*')) aria-current="page" @endif>Groups</a>
    @endcan
    @can('admin.roles.view')
        <a href="{{ route('admin.roles.index') }}" @if(request()->routeIs('admin.roles.*')) aria-current="page" @endif>Roles</a>
    @endcan
    @can('admin.permissions.view')
        <a href="{{ route('admin.permissions.index') }}" @if(request()->routeIs('admin.permissions.*')) aria-current="page" @endif>Permissions</a>
    @endcan
    @can('admin.modules.view')
        <a href="{{ route('admin.modules.index') }}" @if(request()->routeIs('admin.modules.*')) aria-current="page" @endif>Modules</a>
    @endcan
</nav>
