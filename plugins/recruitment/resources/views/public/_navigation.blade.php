<nav class="admin-nav" aria-label="{{ __('recruitment::public._navigation.blade.recruitment_sections') }}">
    <a
        href="{{ route('recruitment.index') }}"
        @if(request()->routeIs('recruitment.index', 'recruitment.show')) aria-current="page" @endif
    >{{ __('terms.open_roles') }}</a>
    @auth
        <a
            href="{{ route('recruitment.applications.index') }}"
            @if(request()->routeIs('recruitment.applications.*')) aria-current="page" @endif
        >{{ __('terms.my_applications') }}</a>
    @endauth
</nav>
