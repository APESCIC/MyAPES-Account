@extends('layouts.app')

@section('title', 'Help | MyAPES Core')

@section('content')
    <article class="panel legal-page" data-legal-page="help">
        <p class="eyebrow">Public guide</p>
        <h1>Help</h1>
        <p class="muted">Short answers for public accounts, staff sign-in, and how to reach APES CIC from MyAPES Core.</p>
        <x-mascot-tip />
        @include('legal._nav')

        <section>
            <h2>Which sign-in should I use?</h2>
            <p><a href="{{ route('public.login') }}">Public Login</a> is for service users who created a MyAPES account. Use your username or email and the password you chose at register.</p>
            <p><a href="{{ route('staff.login') }}">Staff Login</a> is for APES staff and administrators. Those accounts sign in through APES Cloudron, not the public password form.</p>
        </section>

        <section>
            <h2>Create or recover a public account</h2>
            <p>Use <a href="{{ route('public.register') }}">Register</a> to create a public account, then open the verification link we send to your email before you finish profile setup.</p>
            <p>If you cannot sign in, Public Login has a forgot-password link for local public accounts only. Staff and Cloudron directory passwords stay on Cloudron.</p>
        </section>

        <section>
            <h2>Once you are signed in</h2>
            <ul>
                <li>Dashboard shows work that needs you, then the services you selected.</li>
                <li>Profile holds your contact details and which MyAPES services you use.</li>
                <li>APES CIC is for organisational support tickets and formal cases, including privacy requests.</li>
                <li>APES Shelter and Rescue holds pet profiles and rescue casework.</li>
                <li>APES Pet Care Clinic holds clinic pet records, tickets, and consultations.</li>
            </ul>
        </section>

        <section>
            <h2>Ask APES CIC for help</h2>
            <p>Signed-in public users should open a ticket in the service they need. Use an APES CIC case when the matter is a privacy request, complaint, or other formal casework.</p>
            <p>If something in the software looks broken, the App Support links in the sidebar go to the MyAPES Core GitHub repository, issue form, and discussions.</p>
        </section>

        <section>
            <h2>Privacy and data requests</h2>
            <p>If you already have a public account, sign in and open an APES CIC case for a privacy request.</p>
            <p>If you cannot sign in, <a href="{{ route('public.register') }}">create a public account</a> first, or contact APES CIC through the <a href="https://www.apes.org.uk" rel="noopener noreferrer">APES website</a>.</p>
        </section>

        <section>
            <h2>Notices</h2>
            <p>Read the <a href="{{ route('privacy') }}">privacy notice</a>, <a href="{{ route('cookies') }}">cookie notice</a>, and <a href="{{ route('terms') }}">terms of use</a> for how the portal handles information and account use.</p>
        </section>
    </article>
@endsection
