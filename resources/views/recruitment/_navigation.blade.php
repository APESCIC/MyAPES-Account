<nav class="admin-nav" aria-label="Recruitment sections">
    <a
        href="{{ route('recruitment.index') }}"
        @if(request()->routeIs('recruitment.index', 'recruitment.show')) aria-current="page" @endif
    >Open roles</a>
    @auth
        <a
            href="{{ route('recruitment.applications.index') }}"
            @if(request()->routeIs('recruitment.applications.*')) aria-current="page" @endif
        >My applications</a>
    @endauth
</nav>
