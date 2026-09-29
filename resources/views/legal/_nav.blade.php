@php
    $pages = [
        'privacy' => 'Privacy',
        'cookies' => 'Cookies',
        'help' => 'Help',
        'terms' => 'Terms',
    ];
@endphp
<nav aria-label="{{ __('public.legal._nav.blade.legal_and_help_pages') }}">
    <ul class="legal-nav">
        @foreach ($pages as $name => $label)
            <li>
                <a href="{{ route($name) }}" @if (request()->routeIs($name)) aria-current="page" @endif>{{ $label }}</a>
            </li>
        @endforeach
    </ul>
</nav>
