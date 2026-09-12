@extends('layouts.app')

@section('title', 'Cookie notice | MyAPES Core')

@section('content')
    <article class="panel legal-page" data-legal-page="cookies">
        <p class="eyebrow">Public notice</p>
        <h1>Cookie notice</h1>
        <p class="muted">The cookies and similar storage MyAPES Core uses to keep the portal working. Last updated 12 September 2026.</p>
        @include('legal._nav')

        <section>
            <h2>What we use</h2>
            <p>MyAPES Core sets a small number of essential cookies so the site can sign you in, protect forms, and remember a public session if you ask it to. We do not set advertising cookies, and we do not use third-party analytics cookies on these pages.</p>
        </section>

        <section>
            <h2>Essential cookies</h2>
            <ul>
                <li><strong>Session.</strong> Keeps you signed in while you move between pages and expires when the session ends.</li>
                <li><strong>Security token.</strong> Helps stop another site from submitting a form in your name.</li>
                <li><strong>Remember me.</strong> Set only if you tick Remember me on Public Login, so a public account can stay signed in on that browser for longer.</li>
            </ul>
            <p>Staff sign-in through APES Cloudron may also use cookies from that directory so the staff session can start and return to MyAPES Core.</p>
        </section>

        <section>
            <h2>Storage that is not a cookie</h2>
            <p>The theme control (light or dark) is stored in your browser’s local storage so the next visit can keep your choice. That setting stays on your device and is not sent as a cookie.</p>
        </section>

        <section>
            <h2>Managing cookies</h2>
            <p>You can delete or block cookies in your browser. If you block essential cookies, Public Login, Staff Login, and most signed-in pages will not work as intended.</p>
            <p>The <a href="{{ route('privacy') }}">privacy notice</a> explains how APES CIC uses personal information. The <a href="{{ route('terms') }}">terms of use</a> cover using the service.</p>
        </section>
    </article>
@endsection
