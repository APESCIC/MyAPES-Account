<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\SecureUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit', [
            'profile' => request()->user()->profile,
        ]);
    }

    public function update(
        Request $request,
        SecureUploadService $secureUploadService,
        AuditLogger $auditLogger
    ): RedirectResponse {
        $validated = $request->validate([
            'preferred_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'organization' => ['nullable', 'string', 'max:255'],
            'support_needs' => ['nullable', 'string'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $existingProfile = $request->user()->profile;
        $previousAvatarPath = $existingProfile?->avatar_path;

        $payload = [
            'preferred_name' => $validated['preferred_name'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'organization' => $validated['organization'] ?? null,
            'support_needs' => $validated['support_needs'] ?? null,
        ];

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

        if ($request->hasFile('avatar') && is_string($previousAvatarPath) && $previousAvatarPath !== '' && $previousAvatarPath !== $profile->avatar_path) {
            $secureUploadService->deleteIfPresent($previousAvatarPath);
        }

        $auditLogger->record('profile.updated', $request->user(), $profile, [
            'avatar_updated' => $request->hasFile('avatar'),
        ]);

        return redirect()->route('profile.edit')->with('status', 'Profile updated.');
    }
}
