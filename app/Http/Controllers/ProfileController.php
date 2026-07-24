<?php

namespace App\Http\Controllers;

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

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'preferred_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'organization' => ['nullable', 'string', 'max:255'],
            'support_needs' => ['nullable', 'string'],
            'avatar' => ['nullable', 'image', 'max:3072'],
        ]);

        $payload = [
            'preferred_name' => $validated['preferred_name'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'organization' => $validated['organization'] ?? null,
            'support_needs' => $validated['support_needs'] ?? null,
        ];

        if ($request->hasFile('avatar')) {
            $payload['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        $request->user()->profile()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $payload
        );

        return redirect()->route('profile.edit')->with('status', 'Profile updated.');
    }
}
