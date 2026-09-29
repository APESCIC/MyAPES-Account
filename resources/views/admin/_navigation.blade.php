@php
    $adminSearchEntries = app(\App\Services\AdminSearchCatalogue::class)->entriesFor(auth()->user());
@endphp
<div class="admin-shell-tools" data-admin-search>
    <nav class="admin-nav" aria-label="{{ __('admin.nav.admin_sections') }}">
        @can('admin.analytics.view')
            <a href="{{ route('admin.index') }}" @if(request()->routeIs('admin.index')) aria-current="page" @endif>{{ __('admin.nav.overview') }}</a>
        @endcan
        @can('admin.users.view')
            <a href="{{ route('admin.users.index', ['account_type' => 'public']) }}" @if(request()->routeIs('admin.users.*') && request('account_type') === 'public') aria-current="page" @endif>{{ __('admin.nav.public_users') }}</a>
            <a href="{{ route('admin.users.index', ['account_type' => 'staff']) }}" @if(request()->routeIs('admin.users.*') && request('account_type') === 'staff') aria-current="page" @endif>{{ __('admin.nav.staff') }}</a>
        @endcan
        @canany(['admin.groups.view', 'admin.roles.view', 'admin.permissions.view'])
            <a href="{{ route('admin.access.index') }}" @if(request()->routeIs('admin.access.*', 'admin.groups.*', 'admin.roles.*', 'admin.permissions.*')) aria-current="page" @endif>{{ __('admin.nav.access') }}</a>
        @endcanany
        @can('admin.modules.view')
            <a href="{{ route('admin.organisation-modules.index') }}" @if(request()->routeIs('admin.organisation-modules.*')) aria-current="page" @endif>{{ __('admin.nav.modules') }}</a>
            <a href="{{ route('admin.modules.index') }}" @if(request()->routeIs('admin.modules.*')) aria-current="page" @endif>{{ __('admin.nav.plugins') }}</a>
        @endcan
        @can('admin.maintenance.manage')
            <a href="{{ route('admin.maintenance.index') }}" @if(request()->routeIs('admin.maintenance.*')) aria-current="page" @endif>{{ __('admin.nav.maintenance') }}</a>
        @endcan
    </nav>

    @if($adminSearchEntries !== [])
        <div class="admin-search">
            <label class="admin-search__label" for="admin-search-input">{{ __('admin.search.label') }}</label>
            <input
                id="admin-search-input"
                class="admin-search__input"
                type="search"
                data-admin-search-input
                autocomplete="off"
                placeholder="{{ __('admin.search.placeholder') }}"
                aria-controls="admin-search-results"
                aria-describedby="admin-search-hint"
            >
            <p id="admin-search-hint" class="admin-search__hint muted">{{ __('admin.search.hint') }}</p>
            <ul
                id="admin-search-results"
                class="admin-search__results"
                data-admin-search-results
                role="listbox"
                aria-label="{{ __('admin.search.results') }}"
                hidden
            ></ul>
            <p class="admin-search__empty muted" data-admin-search-empty hidden>{{ __('admin.search.empty') }}</p>
            <script type="application/json" data-admin-search-index>{!! json_encode($adminSearchEntries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
        </div>
    @endif
</div>
