@extends('layouts.app')

@section('title', 'Privacy notice | MyAPES Core')

@section('content')
    <article class="panel legal-page" data-legal-page="privacy">
        <p class="eyebrow">Public notice</p>
        <h1>Privacy notice</h1>
        <p class="muted">How Association of Protecting Exotic Species CIC uses personal information in MyAPES Core. Last updated 12 September 2026.</p>
        @include('legal._nav')

        <section>
            <h2>Who we are</h2>
            <p>Association of Protecting Exotic Species CIC (CIC No. 16253848) is the controller for personal information processed in MyAPES Core. This notice covers the public portal used by service users, and the staff tools that support APES CIC, APES Shelter and Rescue, and APES Pet Care Clinic.</p>
            <p>You can also read more about APES CIC on the <a href="https://www.apes.org.uk" rel="noopener noreferrer">APES website</a>.</p>
        </section>

        <section>
            <h2>Information we hold</h2>
            <p>When you create a public account we store the name, email, and username you give us, together with a hashed password. After you sign in we may also hold:</p>
            <ul>
                <li>UK contact details and optional contact preferences from your profile</li>
                <li>The MyAPES services you choose, such as APES CIC, Shelter and Rescue, or Pet Care Clinic</li>
                <li>Pet profiles, tickets, cases, and comments you create or that staff share with you</li>
                <li>Technical records needed to keep the service secure, such as sign-in and verification events</li>
            </ul>
            <p>Staff accounts are created through APES Cloudron. Those accounts follow the same service records once a person is signed in, and directory groups decide what they can open.</p>
        </section>

        <section>
            <h2>Why we use it</h2>
            <p>We use this information to run MyAPES Core: to let you sign in, finish account setup, contact you about the services you asked for, and handle tickets, cases, and pet records. We rely on the steps you take in the app, our legitimate interest in running a safe support portal, and our public-interest work as a community interest company.</p>
            <p>We do not sell personal information, and we do not use it for advertising.</p>
        </section>

        <section>
            <h2>Who can see it</h2>
            <p>Public users see their own profile, pets, and the tickets or cases available to them. Staff and administrators see the records their role allows, so they can respond to support and animal-care work. Hosting and email providers process information only as needed to run the service.</p>
        </section>

        <section>
            <h2>How long we keep it</h2>
            <p>We keep account and service records while you use MyAPES Core and for as long as we need them to deliver support, meet safeguarding or animal-welfare duties, or meet a legal requirement. Audit records are retained for a limited period and then removed.</p>
        </section>

        <section>
            <h2>Your rights</h2>
            <p>You can ask APES CIC for a copy of the personal information we hold, and you can ask us to correct it or, where the law allows, delete it or limit how we use it. You can also complain to the Information Commissioner's Office if you are unhappy with how we handle your information.</p>
            <p>Signed-in public users can start a privacy request through APES CIC cases. If you cannot sign in, create a <a href="{{ route('public.register') }}">public account</a> and open a case, or contact APES CIC through the <a href="https://www.apes.org.uk" rel="noopener noreferrer">APES website</a>.</p>
        </section>

        <section>
            <h2>Cookies and related notices</h2>
            <p>MyAPES Core uses a small set of essential cookies so you can sign in and stay signed in. The <a href="{{ route('cookies') }}">cookie notice</a> explains those in more detail. Using the service is also covered by the <a href="{{ route('terms') }}">terms of use</a>.</p>
        </section>
    </article>
@endsection
