<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MyAPES Account')</title>
    <meta name="description" content="MyAPES Account service portal for APES CIC, APES Shelter and Rescue, and APES Pet Care.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'MyAPES Account')">
    <meta property="og:description" content="MyAPES Account service portal for APES CIC service users and staff.">
    <meta property="og:image" content="{{ asset('og-image.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'MyAPES Account')">
    <meta name="twitter:description" content="MyAPES Account service portal for APES CIC service users and staff.">
    <meta name="twitter:image" content="{{ asset('og-image.png') }}">
    <meta name="theme-color" content="#0f172a">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <style>
        :root { color-scheme: light; }
        body { font-family: Arial, sans-serif; margin: 0; background: #f5f7fb; color: #1f2a37; }
        header { background: #0f172a; color: #fff; padding: 1rem 1.25rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
        nav { display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; }
        nav a { color: #e2e8f0; text-decoration: none; padding: .35rem .6rem; border-radius: .4rem; }
        nav a:hover { background: rgba(255,255,255,.12); }
        main { max-width: 1100px; margin: 1.5rem auto; padding: 0 1rem 2rem; }
        .panel { background: #fff; border-radius: .8rem; box-shadow: 0 8px 20px rgba(15, 23, 42, .06); padding: 1rem; margin-bottom: 1rem; }
        .grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit,minmax(240px,1fr)); }
        .service-label { display: inline-block; color: #fff; font-weight: bold; border-radius: 999px; padding: .2rem .7rem; font-size: .85rem; }
        .apes-cic { background: #0f766e; }
        .apes-shelter { background: #7c3aed; }
        .apes-petcare { background: #d97706; }
        label { display: block; font-weight: 700; margin: .4rem 0 .2rem; }
        input, select, textarea, button { font: inherit; border-radius: .45rem; border: 1px solid #cbd5e1; padding: .45rem .6rem; width: 100%; box-sizing: border-box; }
        textarea { min-height: 80px; }
        button { width: auto; background: #2563eb; color: #fff; border: 0; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: .55rem; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
        .row { display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap: .8rem; }
        .muted { color: #64748b; font-size: .9rem; }
        .status { padding: .15rem .5rem; border-radius: .5rem; background: #e2e8f0; font-size: .82rem; text-transform: uppercase; }
        .actions { display: flex; gap: .4rem; align-items: center; flex-wrap: wrap; }
        .alert { border-left: 4px solid #059669; background: #ecfdf5; padding: .6rem .75rem; margin-bottom: 1rem; border-radius: .4rem; }
        .error-list { background: #fef2f2; border-left: 4px solid #dc2626; padding: .6rem .75rem; margin-bottom: 1rem; border-radius: .4rem; }
        form.inline { display: inline; }
        footer { border-top: 1px solid #e2e8f0; color: #475569; text-align: center; padding: 1rem; margin-top: 2rem; font-size: .9rem; }
        .hero-image { width: 100%; border-radius: .8rem; display: block; margin-bottom: 1rem; }
        .qa-switcher { margin-bottom: 1rem; background: #fffbeb; border-left: 4px solid #f59e0b; padding: .75rem; border-radius: .4rem; }
        .qa-switcher-forms { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .5rem; }
        .qa-switcher-forms form { display: inline; }
        .qa-switcher-forms button { background: #92400e; }
    </style>
</head>
<body>
<header>
    <div>
        <strong>MyAPES Account</strong>
        <div class="muted">Association of Protecting Exotic Species CIC</div>
    </div>
    @auth
        <nav>
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('profile.edit') }}">Profile</a>
            <a href="{{ route('apes-cic.tickets.index') }}">APES CIC</a>
            <a href="{{ route('shelter.pets.index') }}">APES Shelter</a>
            <a href="{{ route('petcare.pets.index') }}">APES Pet Care</a>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.index') }}">Admin</a>
            @endif
            <form method="post" action="{{ route('auth.logout') }}" class="inline">
                @csrf
                <button type="submit">Logout</button>
            </form>
        </nav>
    @endauth
    @guest
        <nav>
            <a href="{{ route('public.login') }}">Public Login</a>
            <a href="{{ route('public.register') }}">Register</a>
            <a href="{{ route('staff.login') }}">Staff Login</a>
        </nav>
    @endguest
</header>
<main>
    @if(app()->environment(['local', 'testing']))
        <div class="qa-switcher">
            <strong>Local QA mode:</strong> Public Login auto-signs in to the seeded public account.
            Use quick role switch to validate staff/admin flows.
            <div class="qa-switcher-forms">
                <form method="post" action="{{ route('qa.switch-role') }}">
                    @csrf
                    <input type="hidden" name="role" value="service_user">
                    <button type="submit">Switch to Public</button>
                </form>
                <form method="post" action="{{ route('qa.switch-role') }}">
                    @csrf
                    <input type="hidden" name="role" value="staff">
                    <button type="submit">Switch to Staff</button>
                </form>
                <form method="post" action="{{ route('qa.switch-role') }}">
                    @csrf
                    <input type="hidden" name="role" value="admin">
                    <button type="submit">Switch to Admin</button>
                </form>
            </div>
        </div>
    @endif
    @if(session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif
    @if($errors->any())
        <div class="error-list">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif
    @yield('content')
</main>
<footer>© 2026 Association of Protecting Exotic Species CIC · CIC No: 16253848</footer>
</body>
</html>
