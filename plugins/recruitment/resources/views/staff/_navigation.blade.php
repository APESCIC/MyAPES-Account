<nav class="admin-nav" aria-label="{{ __('recruitment::staff.staff._navigation.blade.recruitment_manage_sections') }}">
    @canany(['apes-cic.recruitment.view-all', 'apes-cic.recruitment.view-own'])
        <a
            href="{{ route('apes-cic.recruitment.index') }}"
            @if(request()->routeIs('apes-cic.recruitment.*') && ! request()->routeIs('apes-cic.recruitment.applications.*')) aria-current="page" @endif
        >
            <i data-lucide="clipboard-list" aria-hidden="true"></i>
            <span>{{ __('terms.recruitment_roles') }}</span>
        </a>
    @endcanany
    @canany(['apes-cic.recruitment.view-all', 'apes-cic.recruitment.review-applications'])
        <a
            href="{{ route('apes-cic.recruitment.applications.index') }}"
            @if(request()->routeIs('apes-cic.recruitment.applications.*')) aria-current="page" @endif
        >
            <i data-lucide="inbox" aria-hidden="true"></i>
            <span>{{ __('terms.applications') }}</span>
        </a>
    @endcanany
</nav>
