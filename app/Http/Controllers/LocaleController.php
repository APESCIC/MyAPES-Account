<?php

namespace App\Http\Controllers;

use App\Core\Accounts\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Guest/session locale switcher (#275). Hidden while only one locale is supported.
 */
class LocaleController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $supported = array_keys(config('app.supported_locales', ['en_GB' => 'English (UK)']));

        if (count($supported) < 2) {
            abort(404);
        }

        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in($supported)],
        ]);

        $locale = $validated['locale'];

        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null) {
            $user->forceFill(['locale' => $locale])->save();
        }

        $request->session()->put('locale', $locale);

        return back();
    }
}
