<?php

namespace App\Http\Controllers\Auth;

use App\Core\Accounts\User;
use App\Http\Controllers\Controller;
use App\Services\SessionAuthorizationContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ConfirmPasswordController extends Controller
{
    public function __construct(
        private readonly SessionAuthorizationContext $authorizationContext,
    ) {}

    public function show(Request $request): View
    {
        $this->ensureLocalPasswordIdentity($request);

        return view('auth.confirm-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->ensureLocalPasswordIdentity($request);

        if (! is_string($user->password) || $user->password === '' || ! Hash::check((string) $request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->regenerate();
        $this->authorizationContext->recordPassword($request, $user);
        $request->session()->passwordConfirmed();

        return redirect()->to($this->intendedUrl($request));
    }

    private function ensureLocalPasswordIdentity(Request $request): User
    {
        $user = $request->user();

        if ($user === null || ! $user->isLocalPasswordIdentity()) {
            abort(403, __('auth.confirm_password.local_only'));
        }

        return $user;
    }

    private function intendedUrl(Request $request): string
    {
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended) || $intended === '') {
            return route('profile.edit');
        }

        $intendedPath = parse_url($intended, PHP_URL_PATH);
        $passwordUpdatePath = parse_url(route('profile.password.update'), PHP_URL_PATH);
        $usernameUpdatePath = parse_url(route('profile.username.update'), PHP_URL_PATH);

        if (is_string($passwordUpdatePath) && $intendedPath === $passwordUpdatePath) {
            return route('profile.edit').'#change-password';
        }

        if (is_string($usernameUpdatePath) && $intendedPath === $usernameUpdatePath) {
            return route('profile.edit').'#change-username';
        }

        return $intended;
    }
}
