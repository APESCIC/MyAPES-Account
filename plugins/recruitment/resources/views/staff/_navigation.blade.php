<nav class="admin-nav" aria-label="{{ __('recruitment::staff.staff._navigation.blade.recruitment_manage_sections') }}">
    @canany(['apes-cic.recruitment.view-all', 'apes-cic.recruitment.view-own'])
        <a
            href="{{ route('apes-cic.recruitment.index') }}"
            @if(request()->routeIs('apes-cic.recruitment.*') && ! request()->routeIs('apes-cic.recruitment.applications.*')) aria-current="page" @endif
        >{{ __('terms.recruitment_roles') }}</a>
    @endcanany
    @canany(['apes-cic.recruitment.view-all', 'apes-cic.recruitment.review-applications'])
        <a
            href="{{ route('apes-cic.recruitment.applications.index') }}"
            @if(request()->routeIs('apes-cic.recruitment.applications.*')) aria-current="page" @endif
        >{{ __('terms.applications') }}</a>
    @endcanany
</nav>
