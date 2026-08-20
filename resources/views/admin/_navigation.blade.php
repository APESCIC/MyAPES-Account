<nav class="admin-nav" aria-label="Admin sections">
    @can('admin.analytics.view')
        <a href="{{ route('admin.index') }}" @if(request()->routeIs('admin.index')) aria-current="page" @endif>Overview</a>
    @endcan
    @can('admin.users.view')
        <a href="{{ route('admin.users.index', ['account_type' => 'public']) }}" @if(request()->routeIs('admin.users.*') && request('account_type') === 'public') aria-current="page" @endif>Public users</a>
        <a href="{{ route('admin.users.index', ['account_type' => 'staff']) }}" @if(request()->routeIs('admin.users.*') && request('account_type') === 'staff') aria-current="page" @endif>Staff</a>
    @endcan
</nav>
