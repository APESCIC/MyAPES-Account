@extends('layouts.app')

@section('title', 'Register | MyAPES Account')

@section('content')
    <div class="panel">
        <h1>Create public account</h1>
        <p class="muted">Register to access services, your profile, and your pets.</p>
        <x-mascot-tip />
        <form method="post" action="{{ route('public.register.submit') }}">
            @csrf
            <label for="name">Full name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required>

            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>

            <label for="username">Username</label>
            <input id="username" type="text" name="username" value="{{ old('username') }}" minlength="3" maxlength="30" required>

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>

            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required>

            <fieldset>
                <legend>Select at least one MyAPES service</legend>
                @foreach (['apes-cic' => 'APES CIC', 'shelter-rescue' => 'APES Shelter and Rescue', 'pet-care-clinic' => 'APES Pet Care Clinic'] as $key => $label)
                    <label class="inline-check">
                        <input type="checkbox" name="services[]" value="{{ $key }}" @checked(in_array($key, old('services', []), true))>
                        {{ $label }}
                    </label>
                @endforeach
            </fieldset>

            <div class="actions">
                <button type="submit">Register</button>
                <a href="{{ route('public.login') }}">Already have an account?</a>
            </div>
        </form>
    </div>
@endsection
