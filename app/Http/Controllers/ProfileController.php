<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\ContactPreferenceUpdater;
use App\Services\SecureUploadService;
use App\Services\UkPhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit', [
            'profile' => request()->user()->profile,
            'preference' => request()->user()->contactPreference,
            'selectedServices' => request()->user()->serviceSelections()->pluck('sub_core_key')->all(),
        ]);
    }

    public function update(
        Request $request,
        SecureUploadService $secureUploadService,
        AuditLogger $auditLogger,
        ContactPreferenceUpdater $preferences,
        OnboardingController $onboarding,
        UkPhoneNumber $phones,
    ): RedirectResponse {
        $onboarding->normalizePhones($request, $phones);
        $validated = $request->validate([
            'preferred_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'organization' => ['nullable', 'string', 'max:255'],
            'support_needs' => ['nullable', 'string'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'town_city' => ['required', 'string', 'max:255'],
            'county' => ['nullable', 'string', 'max:255'],
            'postcode' => ['required', 'string', 'max:16', 'regex:/^[A-Za-z]{1,2}\d[A-Za-z\d]?\s*\d[A-Za-z]{2}$/'],
            'mobile_number' => ['required', 'string', 'max:32', 'regex:/^\+44\d{9,10}$/'],
            'landline_number' => ['nullable', 'string', 'max:32', 'regex:/^\+44\d{9,10}$/'],
            'whatsapp_number' => ['nullable', 'string', 'max:32', 'regex:/^\+44\d{9,10}$/'],
            'telegram_username' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9_]{5,32}$/'],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['string', Rule::in(['apes-cic', 'shelter-rescue', 'pet-care-clinic'])],
            'contact_preferences_confirmed' => ['accepted'],
        ]);

        $existingProfile = $request->user()->profile;
        $previousAvatarPath = $existingProfile?->avatar_path;

        $payload = [
            'preferred_name' => $validated['preferred_name'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'organization' => $validated['organization'] ?? null,
            'support_needs' => $validated['support_needs'] ?? null,
        ] + $onboarding->profilePayload($validated);

        if ($request->hasFile('avatar')) {
            $payload['avatar_path'] = $secureUploadService->storeImage(
                $request->file('avatar'),
                'avatars',
                'avatar'
            );
        }

        $profile = $request->user()->profile()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $payload
        );

        $request->user()->serviceSelections()->whereNotIn('sub_core_key', $validated['services'])->delete();
        foreach ($validated['services'] as $service) {
            $request->user()->serviceSelections()->firstOrCreate(['sub_core_key' => $service]);
        }
        $preferences->update(
            $request->user(),
            $request->user(),
            $onboarding->contactChoices($request),
            'preferences',
        );

        if ($request->hasFile('avatar') && is_string($previousAvatarPath) && $previousAvatarPath !== '' && $previousAvatarPath !== $profile->avatar_path) {
            $secureUploadService->deleteIfPresent($previousAvatarPath);
        }

        $auditLogger->record('profile.updated', $request->user(), $profile, [
            'avatar_updated' => $request->hasFile('avatar'),
        ]);

        return redirect()->route('profile.edit')->with('status', 'Profile updated.');
    }
}
