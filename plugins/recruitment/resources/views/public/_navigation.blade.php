<nav class="admin-nav" aria-label="{{ __('recruitment::public._navigation.blade.recruitment_sections') }}">
    <a
        href="{{ route('recruitment.index') }}"
        @if(request()->routeIs('recruitment.index', 'recruitment.show')) aria-current="page" @endif
    >
        <i data-lucide="briefcase" aria-hidden="true"></i>
        <span>{{ __('terms.open_roles') }}</span>
    </a>
    @auth
        <a
            href="{{ route('recruitment.applications.index') }}"
            @if(request()->routeIs('recruitment.applications.*')) aria-current="page" @endif
        >
            <i data-lucide="clipboard-list" aria-hidden="true"></i>
            <span>{{ __('terms.my_applications') }}</span>
        </a>
    @endauth
</nav>
