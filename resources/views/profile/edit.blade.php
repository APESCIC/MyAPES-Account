@extends('layouts.app')

@section('title', 'Profile | MyAPES Core')

@section('content')
    <div class="panel">
        <h1>{{ __('public.profile.edit.blade.your_profile') }}</h1>
        <p class="muted">{{ __('public.profile.edit.blade.core_account_details_used_across_all_apes_services') }}</p>
        <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('put')
            <div class="row">
                <div>
                    <label for="preferred_name">{{ __('public.profile.edit.blade.preferred_name') }}</label>
                    <input id="preferred_name" name="preferred_name" value="{{ old('preferred_name', $profile?->preferred_name) }}">
                </div>
                <div>
                    <label for="phone">{{ __('public.profile.edit.blade.phone') }}</label>
                    <input id="phone" name="phone" value="{{ old('phone', $profile?->phone) }}">
                </div>
                <div>
                    <label for="organization">{{ __('public.profile.edit.blade.organisation') }}</label>
                    <input id="organization" name="organization" value="{{ old('organization', $profile?->organization) }}">
                </div>
            </div>
            <label for="support_needs">{{ __('public.profile.edit.blade.support_needs_or_access_notes') }}</label>
            <textarea id="support_needs" name="support_needs">{{ old('support_needs', $profile?->support_needs) }}</textarea>
            @include('profile._account-fields')
            <label for="avatar">{{ __('public.profile.edit.blade.avatar_photo') }}</label>
            <input id="avatar" type="file" name="avatar" accept="image/*">
            <div class="actions">
                <button type="submit">{{ __('public.profile.edit.blade.save_profile') }}</button>
            </div>
        </form>
        <x-locale-switcher />
    </div>

    <div class="panel" id="account-email">
        <h2>{{ __('public.profile.edit.blade.account_email') }}</h2>
        <p class="muted">{{ __('public.profile.edit.blade.notifications_and_password_reset_mail_go_to_this_addres') }}</p>
        <dl class="admin-definition-list">
            <div>
                <dt>{{ __('public.profile.edit.blade.email') }}</dt>
                <dd>{{ auth()->user()->email }}</dd>
            </div>
            <div>
                <dt>{{ __('auth.common.username') }}</dt>
                <dd>{{ auth()->user()->username }}</dd>
            </div>
        </dl>
    </div>

    @if($canChangeLocalUsername)
        <div class="panel" id="change-username">
            <h2>{{ __('auth.username_change.heading') }}</h2>
            <p class="muted">{{ __('auth.username_change.intro') }}</p>
            <form method="post" action="{{ route('profile.username.update') }}">
                @csrf
                @method('put')
                <label for="username">{{ __('auth.common.username') }}</label>
                <input
                    id="username"
                    type="text"
                    name="username"
                    value="{{ old('username', auth()->user()->username) }}"
                    autocomplete="username"
                    minlength="3"
                    maxlength="30"
                    pattern="[A-Za-z0-9](?:[A-Za-z0-9._-]{1,28}[A-Za-z0-9])"
                    required
                >
                <p class="muted">{{ __('auth.register.username_guidance') }}</p>
                <div class="actions">
                    <button type="submit">{{ __('auth.username_change.submit') }}</button>
                </div>
            </form>
        </div>
    @endif

    @if($canChangeLocalPassword)
        <div class="panel" id="change-password">
            <h2>{{ __('public.profile.edit.blade.change_password') }}</h2>
            <p class="muted">{{ __('public.profile.edit.blade.enter_your_current_password_then_choose_a_new_one_for_t') }}</p>
            <form method="post" action="{{ route('profile.password.update') }}">
                @csrf
                @method('put')
                <label for="current_password">{{ __('public.profile.edit.blade.current_password') }}</label>
                <input id="current_password" type="password" name="current_password" autocomplete="current-password" required>

                <label for="password">{{ __('public.profile.edit.blade.new_password') }}</label>
                <input id="password" type="password" name="password" autocomplete="new-password" required>

                <label for="password_confirmation">{{ __('public.profile.edit.blade.confirm_new_password') }}</label>
                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>

                <div class="actions">
                    <button type="submit">{{ __('public.profile.edit.blade.update_password') }}</button>
                </div>
            </form>
        </div>
    @else
        <div class="panel">
            <h2>{{ __('public.profile.edit.blade.password') }}</h2>
            <p class="muted">{{ __('public.profile.edit.blade.this_account_uses_cloudron_directory_sign_in_change_you') }}</p>
        </div>
    @endif
@endsection
