@extends('layouts.app')

@section('title', __('auth.register.title'))

@section('content')
    <div class="panel">
        <h1>{{ __('auth.register.heading') }}</h1>
        <p class="muted">{{ __('auth.register.intro') }}</p>
        <x-mascot-tip />
        <form method="post" action="{{ route('public.register.submit') }}">
            @csrf
            <label for="name">{{ __('auth.register.full_name') }}</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" required>

            <label for="email">{{ __('auth.common.email') }}</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>

            <label for="username">{{ __('auth.common.username') }}</label>
            <input id="username" type="text" name="username" value="{{ old('username') }}" autocomplete="username" minlength="3" maxlength="30" required>

            <label for="password">{{ __('auth.common.password') }}</label>
            <input id="password" type="password" name="password" autocomplete="new-password" required>

            <label for="password_confirmation">{{ __('auth.register.confirm_password') }}</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>

            <fieldset>
                <legend>{{ __('auth.register.services_legend') }}</legend>
                @foreach (['apes-cic' => __('auth.common.apes_cic'), 'shelter-rescue' => __('auth.common.apes_shelter'), 'pet-care-clinic' => __('auth.common.apes_petcare')] as $key => $label)
                    <label class="inline-check">
                        <input type="checkbox" name="services[]" value="{{ $key }}" @checked(in_array($key, old('services', []), true))>
                        {{ $label }}
                    </label>
                @endforeach
            </fieldset>

            <fieldset>
                <legend>{{ __('auth.register.consent_legend') }}</legend>
                <label class="inline-check legal-consent" for="registration_consent">
                    <input
                        id="registration_consent"
                        type="checkbox"
                        name="registration_consent"
                        value="1"
                        @checked((bool) old('registration_consent'))
                        required
                    >
                    <span>
                        {{ __('auth.register.consent_prefix') }}
                        <a href="{{ route('terms') }}">{{ __('auth.register.terms_of_use') }}</a>
                        {{ __('auth.register.consent_and') }}
                        <a
                            href="{{ \App\Support\PrivacyNotice::url() }}"
                            @if (\App\Support\PrivacyNotice::opensExternally()) target="_blank" rel="noopener noreferrer" @endif
                        >{{ __('auth.register.privacy_notice') }}</a>.
                    </span>
                </label>
                <p class="muted legal-register-note">
                    {{ __('auth.register.also_read_prefix') }}
                    <a href="{{ route('cookies') }}">{{ __('auth.register.cookie_notice') }}</a>
                    {{ __('auth.register.or_open') }}
                    <a href="{{ route('help') }}">{{ __('auth.register.help') }}</a>
                    {{ __('auth.register.help_suffix') }}
                </p>
            </fieldset>

            <div class="actions">
                <button type="submit">{{ __('auth.common.register') }}</button>
                <a href="{{ route('public.login') }}">{{ __('auth.register.already_have_account') }}</a>
            </div>
        </form>
    </div>
@endsection
