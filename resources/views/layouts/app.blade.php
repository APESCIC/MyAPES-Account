<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MyAPES Core')</title>
    <meta name="description" content="MyAPES Core service portal for APES CIC, APES Shelter and Rescue, and APES Pet Care Clinic.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'MyAPES Core')">
    <meta property="og:description" content="MyAPES Core service portal for APES CIC service users and staff.">
    <meta property="og:image" content="{{ asset('social/og-image-1200x630.jpg') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'MyAPES Core')">
    <meta name="twitter:description" content="MyAPES Core service portal for APES CIC service users and staff.">
    <meta name="twitter:image" content="{{ asset('social/og-image-1200x630.jpg') }}">
    <meta name="theme-color" content="#f3e4c4">
    <meta name="msapplication-config" content="{{ asset('browserconfig.xml') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicons/favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicons/favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <script>
        (() => {
            try {
                const savedTheme = localStorage.getItem('myapes-theme');
                const theme = savedTheme === 'dark' ? 'dark' : 'light';
                document.documentElement.dataset.theme = theme;
                document.documentElement.style.colorScheme = theme;
            } catch {
                document.documentElement.dataset.theme = 'light';
                document.documentElement.style.colorScheme = 'light';
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>

<header class="mobile-header">
    <a href="{{ route('home') }}" class="mobile-brand" aria-label="MyAPES Core home">
        <img
            src="{{ asset('branding/logo-myapes-account.png') }}"
            srcset="{{ asset('logos/myapes-mark-256x256.png') }} 256w, {{ asset('branding/logo-myapes-account.png') }} 1024w"
            sizes="3.2rem"
            width="1024"
            height="1024"
            alt=""
        >
        <span><strong>MyAPES</strong> Core</span>
    </a>
    <button
        type="button"
        class="sidebar-toggle"
        data-sidebar-toggle
        aria-controls="site-sidebar"
        aria-expanded="false"
        aria-label="Open navigation menu"
    >
        <i data-lucide="menu" aria-hidden="true"></i>
    </button>
</header>

<div class="app-shell">
    <aside id="site-sidebar" class="site-sidebar" data-sidebar aria-label="Site navigation">
        <div class="site-sidebar__inner">
            <button type="button" class="sidebar-close" data-sidebar-close aria-label="Close navigation menu">
                <i data-lucide="x" aria-hidden="true"></i>
            </button>

            <a href="{{ route('home') }}" class="sidebar-brand" aria-label="MyAPES Core home">
                <img
                    src="{{ asset('branding/logo-myapes-account.png') }}"
                    srcset="{{ asset('logos/myapes-mark-256x256.png') }} 256w, {{ asset('branding/logo-myapes-account.png') }} 1024w"
                    sizes="(max-width: 64rem) 8.5rem, 10.75rem"
                    width="1024"
                    height="1024"
                    alt="MyAPES Core"
                >
            </a>

            <nav class="primary-nav" aria-label="Primary navigation">
                @auth
                    <a href="{{ route('dashboard') }}" @class(['primary-nav__link', 'is-active' => request()->routeIs('dashboard')]) @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                        <i data-lucide="layout-dashboard" aria-hidden="true"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="{{ route('profile.edit') }}" @class(['primary-nav__link', 'is-active' => request()->routeIs('profile.*')]) @if(request()->routeIs('profile.*')) aria-current="page" @endif>
                        <i data-lucide="user-round" aria-hidden="true"></i>
                        <span>Profile</span>
                    </a>
                    @foreach($moduleNavigation as $subCoreNavigation)
                        @php
                            $routePrefix = str($subCoreNavigation->subCore->routeName)
                                ->before('.')
                                ->toString();
                            $active = request()->routeIs($routePrefix.'.*');
                        @endphp
                        <a href="{{ route($subCoreNavigation->subCore->routeName) }}" @class(['primary-nav__link', 'is-active' => $active]) @if($active) aria-current="page" @endif>
                            <i data-lucide="{{ $subCoreNavigation->subCore->icon }}" aria-hidden="true"></i>
                            <span>{{ $subCoreNavigation->subCore->name }}</span>
                        </a>
                    @endforeach
                    @can('admin.access')
                        <a href="{{ route('admin.index') }}" @class(['primary-nav__link', 'is-active' => request()->routeIs('admin.index', 'admin.users.*')]) @if(request()->routeIs('admin.index', 'admin.users.*')) aria-current="page" @endif>
                            <i data-lucide="settings" aria-hidden="true"></i>
                            <span>Admin</span>
                        </a>
                    @endcan
                    @can('superadmin.access')
                        <a href="{{ route('superadmin.index') }}" @class(['primary-nav__link', 'is-active' => request()->routeIs('superadmin.*', 'admin.access.*', 'admin.modules.*', 'admin.maintenance.*')]) @if(request()->routeIs('superadmin.*', 'admin.access.*', 'admin.modules.*', 'admin.maintenance.*')) aria-current="page" @endif>
                            <i data-lucide="shield-check" aria-hidden="true"></i>
                            <span>Super Admin</span>
                        </a>
                    @endcan
                @else
                    <a href="{{ route('public.login') }}" @class(['primary-nav__link', 'is-active' => request()->routeIs('public.login')]) @if(request()->routeIs('public.login')) aria-current="page" @endif>
                        <i data-lucide="log-in" aria-hidden="true"></i>
                        <span>Public Login</span>
                    </a>
                    <a href="{{ route('public.register') }}" @class(['primary-nav__link', 'is-active' => request()->routeIs('public.register')]) @if(request()->routeIs('public.register')) aria-current="page" @endif>
                        <i data-lucide="user-plus" aria-hidden="true"></i>
                        <span>Register</span>
                    </a>
                    <a href="{{ route('staff.login') }}" @class(['primary-nav__link', 'is-active' => request()->routeIs('staff.*')]) @if(request()->routeIs('staff.*')) aria-current="page" @endif>
                        <i data-lucide="badge-check" aria-hidden="true"></i>
                        <span>Staff Login</span>
                    </a>
                @endauth
            </nav>

            <div class="sidebar-tools">
                <button type="button" class="sidebar-tool theme-toggle" data-theme-toggle aria-pressed="false" aria-label="Switch to dark theme">
                    <span class="sidebar-tool__icon">
                        <i data-lucide="sun" class="theme-toggle__icon theme-toggle__icon--light" aria-hidden="true"></i>
                        <i data-lucide="moon" class="theme-toggle__icon theme-toggle__icon--dark" aria-hidden="true"></i>
                    </span>
                    <span data-theme-label>Light mode</span>
                    <i data-lucide="chevron-right" class="sidebar-tool__chevron" aria-hidden="true"></i>
                </button>
                @auth
                    <a href="{{ route('profile.edit') }}" class="sidebar-tool" title="{{ auth()->user()->name }}">
                        <span class="sidebar-tool__icon"><i data-lucide="circle-user-round" aria-hidden="true"></i></span>
                        <span class="sidebar-tool__label">{{ auth()->user()->name }}</span>
                    </a>
                    <form method="post" action="{{ route('auth.logout') }}">
                        @csrf
                        <button type="submit" class="sidebar-tool">
                            <span class="sidebar-tool__icon"><i data-lucide="log-out" aria-hidden="true"></i></span>
                            <span>Log out</span>
                        </button>
                    </form>
                @endauth

                <section class="sidebar-support" aria-labelledby="sidebar-support-title">
                    <h2 id="sidebar-support-title" class="sidebar-support__heading">App Support</h2>
                    @include('partials._github-links', ['variant' => 'sidebar'])
                </section>
            </div>
        </div>
    </aside>

    <button type="button" class="sidebar-backdrop" data-sidebar-backdrop aria-label="Close navigation menu" tabindex="-1"></button>

    <div class="app-frame">
        <header class="content-brand" aria-label="MyAPES Core">
            <strong>My<span>APES</span></strong> Core
            <small>Association of Protecting Exotic Species CIC</small>
        </header>

<main id="main-content" class="app-main" tabindex="-1">
    @if(app()->environment(['local', 'testing']))
        @php
            $authorizationProfile = app(\App\Services\AuthorizationProfile::class);
            $activeRole = auth()->user() === null
                ? null
                : $authorizationProfile->qaSelectorFor(auth()->user());
            $activeRoleLabel = auth()->user() === null
                ? 'Guest'
                : $authorizationProfile->displayLabel(auth()->user());
        @endphp
        <section class="qa-switcher" aria-label="Local QA role switcher">
            <div class="qa-switcher__identity">
                <strong>Local QA</strong>
                <span class="qa-switcher__badge">Dev only</span>
            </div>
            <div class="qa-switcher__current">
                <span>Current role:</span>
                <strong><i data-lucide="user-round" aria-hidden="true"></i>{{ $activeRoleLabel }}</strong>
            </div>
            <div class="qa-switcher__forms" aria-label="Switch active role">
                @foreach([
                    'service_user' => 'Public',
                    'student' => 'Student',
                    'volunteer' => 'Volunteer',
                    'staff' => 'Staff',
                    'admin' => 'Admin',
                    'superadmin' => 'Super Admin',
                ] as $role => $label)
                    <form method="post" action="{{ route('qa.switch-role') }}">
                        @csrf
                        <input type="hidden" name="role" value="{{ $role }}">
                        <button type="submit" @class(['qa-role-button', 'is-active' => $activeRole === $role]) aria-pressed="{{ $activeRole === $role ? 'true' : 'false' }}">
                            {{ $label }}
                        </button>
                    </form>
                @endforeach
            </div>
            <span class="qa-switcher__environment">
                <i data-lucide="leaf" aria-hidden="true"></i>
                Development mode · QA environment
            </span>
        </section>
    @endif

    @if(session('status'))
        <div class="alert" role="status">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="error-list" role="alert">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @yield('content')
</main>

<footer class="site-footer">
    <span><strong>MyAPES</strong> Core</span>
    <span>© {{ now()->year }} Association of Protecting Exotic Species CIC · CIC No: 16253848</span>
    <a
        class="site-footer__version"
        href="{{ route('change-log.index') }}"
        aria-label="View the MyAPES Core change log for version v{{ $appVersion }}"
    >v{{ $appVersion }}</a>
</footer>
    </div>
</div>
@if($mascotTip)
    <aside
        class="mascot-dock"
        data-mascot-dock
        data-mascot-route="{{ $mascotTip['route'] }}"
        aria-label="Tip from Spike, the MyAPES bearded dragon"
    >
        <img
            src="{{ asset('mascot/spike-dock.png') }}"
            alt=""
            class="mascot-dock__avatar"
            width="1024"
            height="1024"
        >
        <div class="mascot-dock__bubble">
            <button
                type="button"
                class="mascot-dock__dismiss"
                data-mascot-dismiss
                aria-label="Hide tip"
            >
                <span aria-hidden="true">&times;</span>
            </button>
            <p><strong>{{ $mascotTip['title'] }}</strong> {{ $mascotTip['body'] }}</p>
        </div>
    </aside>
@endif
</body>
</html>
