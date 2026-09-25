<nav class="admin-nav" aria-label="Recruitment manage sections">
    @canany(['apes-cic.recruitment.view-all', 'apes-cic.recruitment.view-own'])
        <a
            href="{{ route('apes-cic.recruitment.index') }}"
            @if(request()->routeIs('apes-cic.recruitment.*') && ! request()->routeIs('apes-cic.recruitment.applications.*')) aria-current="page" @endif
        >Roles</a>
    @endcanany
    @canany(['apes-cic.recruitment.view-all', 'apes-cic.recruitment.review-applications'])
        <a
            href="{{ route('apes-cic.recruitment.applications.index') }}"
            @if(request()->routeIs('apes-cic.recruitment.applications.*')) aria-current="page" @endif
        >Applications</a>
    @endcanany
</nav>
