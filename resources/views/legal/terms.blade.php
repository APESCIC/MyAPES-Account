@extends('layouts.app')

@section('title', 'Terms of use | MyAPES Core')

@section('content')
    <article class="panel legal-page" data-legal-page="terms">
        <p class="eyebrow">Public notice</p>
        <h1>Terms of use</h1>
        <p class="muted">The rules for using MyAPES Core as a public service user or as APES staff. Last updated 12 September 2026.</p>
        @include('legal._nav')

        <section>
            <h2>The service</h2>
            <p>MyAPES Core is the service portal run by Association of Protecting Exotic Species CIC (CIC No. 16253848) for APES CIC, APES Shelter and Rescue, and APES Pet Care Clinic. These terms cover use of the portal. They do not replace veterinary advice, rescue decisions, or a separate agreement APES CIC may have with you.</p>
        </section>

        <section>
            <h2>Accounts</h2>
            <p>Public accounts are for service users. Keep your password to yourself, use accurate details, and tell us if you think someone else has used your account. Staff and administrators must use Staff Login through APES Cloudron and follow the access they are given.</p>
            <p>We may suspend or close an account if these terms are broken, if access is no longer appropriate, or if we need to protect people, animals, or the service.</p>
        </section>

        <section>
            <h2>Using MyAPES Core</h2>
            <ul>
                <li>Use the portal only for genuine APES CIC, shelter, or clinic support.</li>
                <li>Do not upload material you do not have the right to share, or that is harmful, unlawful, or intended to disrupt the service.</li>
                <li>Do not try to open another person’s records, or to bypass sign-in or role checks.</li>
                <li>Treat messages, tickets, and case notes as confidential support records.</li>
            </ul>
        </section>

        <section>
            <h2>Content and availability</h2>
            <p>Records you add stay available to the people who need them for support and animal-care work. We aim to keep MyAPES Core available, but we may take it offline for maintenance, security, or operational reasons. The <a href="{{ route('change-log.index') }}">Change Log Hub</a> lists released changes.</p>
        </section>

        <section>
            <h2>Privacy and cookies</h2>
            <p>The <a href="{{ route('privacy') }}">privacy notice</a> explains how we use personal information. The <a href="{{ route('cookies') }}">cookie notice</a> explains the essential cookies the portal sets. Creating a public account means you accept these terms and the way those notices describe the service.</p>
        </section>

        <section>
            <h2>Changes</h2>
            <p>We may update these terms when the portal or the law changes. The date at the top of this page shows the current version. Continued use after an update means you accept the revised terms.</p>
            <p>Questions about these terms can start from the <a href="{{ route('help') }}">Help</a> page or, once you are signed in, an APES CIC ticket or case.</p>
        </section>
    </article>
@endsection
