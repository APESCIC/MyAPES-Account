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
    <meta property="og:image" content="{{ asset('social/og-image-1200x630.jpg') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'MyAPES Account')">
    <meta name="twitter:description" content="MyAPES Account service portal for APES CIC service users and staff.">
    <meta name="twitter:image" content="{{ asset('social/og-image-1200x630.jpg') }}">
    <meta name="theme-color" content="#008f99">
    <meta name="msapplication-config" content="{{ asset('browserconfig.xml') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicons/favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicons/favicon-32x32.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">
    <link rel="mask-icon" href="{{ asset('favicons/safari-pinned-tab.svg') }}" color="#008f99">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <style>
        :root {
            color-scheme: light;
            --bg: #f9fdf7;
            --surface: #ffffff;
            --surface-soft: #effaf7;
            --ink: #0f172a;
            --muted: #4b5e77;
            --accent: #117d75;
            --accent-strong: #0a615a;
            --sun: #f18c24;
            --berry: #7647d8;
            --line: #d8e6e4;
            --danger: #b91c1c;
            --radius: 1rem;
            --shadow: 0 14px 30px rgba(17, 53, 76, .09);
        }
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            margin: 0;
            background:
                radial-gradient(1600px 600px at -20% -30%, rgba(17, 125, 117, .14), transparent 60%),
                radial-gradient(1300px 500px at 120% -10%, rgba(241, 140, 36, .13), transparent 55%),
                var(--bg);
            color: var(--ink);
        }
        a { color: #0f60d6; text-underline-offset: .15em; }
        a:hover { color: #0c4a9f; }
        header {
            background: linear-gradient(105deg, #0b2e43 0%, #155d73 52%, #2167de 100%);
            color: #fff;
            padding: 1rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            border-bottom: 4px solid rgba(241, 140, 36, .75);
        }
        nav { display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; }
        nav a {
            color: #ecf8ff;
            text-decoration: none;
            padding: .38rem .72rem;
            border-radius: 999px;
            border: 1px solid rgba(237, 252, 255, .25);
            background: rgba(255, 255, 255, .06);
            transition: background .2s ease, border-color .2s ease;
        }
        nav a:hover { background: rgba(255, 255, 255, .16); border-color: rgba(255, 255, 255, .5); }
        main { max-width: 1120px; margin: 1.5rem auto; padding: 0 1rem 2rem; }
        .panel {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .panel-flat { margin: 0; background: var(--surface-soft); }
        .grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit,minmax(240px,1fr)); }
        .service-label {
            display: inline-block;
            color: #fff;
            font-weight: 700;
            border-radius: 999px;
            padding: .25rem .72rem;
            font-size: .83rem;
            letter-spacing: .02em;
        }
        .apes-cic { background: var(--accent); }
        .apes-shelter { background: var(--berry); }
        .apes-petcare { background: #cb6a04; }
        h1, h2, h3 { text-wrap: balance; margin-top: 0; }
        p { text-wrap: pretty; }
        label { display: block; font-weight: 700; margin: .4rem 0 .2rem; }
        input, select, textarea, button {
            font: inherit;
            border-radius: .65rem;
            border: 1px solid #b9cdd6;
            padding: .52rem .65rem;
            width: 100%;
            box-sizing: border-box;
            background: #fff;
        }
        input:focus, select:focus, textarea:focus {
            outline: 2px solid rgba(20, 113, 217, .32);
            outline-offset: 1px;
            border-color: #2f79de;
        }
        textarea { min-height: 90px; }
        button {
            width: auto;
            background: linear-gradient(165deg, #1d85de 0%, #1566be 100%);
            color: #fff;
            border: 0;
            cursor: pointer;
            font-weight: 600;
            transition: transform .18s ease, box-shadow .18s ease;
            box-shadow: 0 6px 12px rgba(11, 84, 157, .3);
        }
        button:hover { transform: translateY(-1px); box-shadow: 0 10px 15px rgba(11, 84, 157, .35); }
        table { width: 100%; border-collapse: collapse; border-radius: .8rem; overflow: hidden; }
        th, td { padding: .58rem; border-bottom: 1px solid #dde9ee; text-align: left; vertical-align: top; }
        th { background: #f1f8fd; color: #1d3852; }
        .row { display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap: .8rem; }
        .muted { color: var(--muted); font-size: .95rem; }
        .status {
            padding: .18rem .52rem;
            border-radius: .6rem;
            background: #e8f4ff;
            color: #16436f;
            font-size: .8rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .actions { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; margin-top: .8rem; }
        .alert, .error-list {
            border: 1px solid transparent;
            padding: .72rem .8rem;
            margin-bottom: 1rem;
            border-radius: .7rem;
        }
        .alert { background: #e8faf1; border-color: #8ed9b3; color: #115b38; }
        .error-list { background: #fff0f0; border-color: #f2b9b9; color: #6e1616; }
        form.inline { display: inline; }
        footer {
            border-top: 1px solid var(--line);
            color: #33495f;
            text-align: center;
            padding: 1rem;
            margin-top: 2rem;
            font-size: .92rem;
            background: rgba(255, 255, 255, .6);
            backdrop-filter: blur(2px);
        }
        .hero-image { width: 100%; border-radius: .8rem; display: block; margin-bottom: 1rem; }
        .logo-hero { width: min(100%, 860px); margin: 0 auto .7rem; border-radius: .8rem; display: block; }
        .brand-lockup { display: flex; flex-direction: column; gap: .42rem; }
        .brand-logo { width: min(62vw, 360px); height: auto; display: block; }
        .brand-note { color: #d1e7f4; }
        .brand-mascot {
            width: 58px;
            height: auto;
            border-radius: 50%;
            border: 2px solid rgba(255, 255, 255, .3);
            background: rgba(255, 255, 255, .15);
            padding: .2rem;
        }
        .mascot-banner {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: .9rem;
            align-items: center;
            background: linear-gradient(130deg, #f4fffb 0%, #e8f5ff 65%, #fff4e9 100%);
            border: 1px solid #cde3ef;
            border-radius: .95rem;
            margin-bottom: 1rem;
            padding: .72rem .9rem;
        }
        .mascot-banner img { width: 72px; height: auto; }
        .mascot-banner strong { color: #124a68; }
        .qa-switcher {
            margin-bottom: 1rem;
            background: #fff8e7;
            border: 1px solid #efd9a0;
            padding: .75rem;
            border-radius: .7rem;
        }
        .qa-switcher-forms { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .5rem; }
        .qa-switcher-forms form { display: inline; }
        .qa-switcher-forms button {
            background: linear-gradient(165deg, #9b5b13 0%, #81440a 100%);
            box-shadow: 0 6px 12px rgba(105, 58, 13, .25);
        }
        .record-photo { max-width: 220px; border-radius: .65rem; border: 1px solid #d2e7ee; }
        .stack-spaced { margin-top: 1rem; }
        .item-divider { padding: .45rem 0; border-bottom: 1px solid #e2edf2; }
        .danger-btn { margin-top: .7rem; background: #b91c1c; box-shadow: none; }
        .inline-check { display: flex; align-items: center; gap: .5rem; margin-top: .65rem; }
        .inline-check input { width: auto; }
        .section-divider { border: 0; border-top: 1px solid #dce8ef; margin: 1rem 0; }
        @media (max-width: 860px) {
            header { flex-direction: column; align-items: flex-start; }
            .mascot-banner { grid-template-columns: 1fr; text-align: center; }
            .mascot-banner img { margin: 0 auto; }
        }
        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>
<header>
    <div class="brand-lockup">
        <img src="{{ asset('logos/myapes-header-dark.svg') }}" alt="MyAPES Account" class="brand-logo">
        <div class="brand-note">Association of Protecting Exotic Species CIC</div>
    </div>
    <img src="{{ asset('mascot/beardie-wave.svg') }}" alt="MyAPES bearded dragon mascot" class="brand-mascot">
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
    <div class="mascot-banner">
        <img src="{{ asset('mascot/beardie-wave.svg') }}" alt="Friendly bearded dragon mascot">
        <div>
            <strong>Meet Ember, your MyAPES helper.</strong>
            <div class="muted">Guiding support tickets, pet care updates, and shelter workflows across every section.</div>
        </div>
    </div>
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
