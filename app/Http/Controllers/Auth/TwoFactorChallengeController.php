<?php

namespace App\Http\Controllers\Auth;

use App\Core\Accounts\User;
use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\SessionAuthorizationContext;
use App\Services\TotpTwoFactorService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private readonly TotpTwoFactorService $twoFactor,
        private readonly SessionAuthorizationContext $authorizationContext,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        if ($this->challengedUser($request) === null) {
            return redirect()->route('public.login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->challengedUser($request);

        if ($user === null) {
            return redirect()->route('public.login');
        }

        $validated = $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $code = trim((string) ($validated['code'] ?? ''));
        $recoveryCode = trim((string) ($validated['recovery_code'] ?? ''));

        if ($code === '' && $recoveryCode === '') {
            throw ValidationException::withMessages([
                'code' => __('auth.two_factor.code_or_recovery_required'),
            ]);
        }

        $passed = false;

        if ($code !== '' && $this->twoFactor->verifyTotp($user, $code)) {
            $passed = true;
        } elseif ($recoveryCode !== '' && $this->twoFactor->consumeRecoveryCode($user, $recoveryCode)) {
            $passed = true;
        }

        if (! $passed) {
            $this->auditLogger->record('auth.two_factor_challenge_failed', $user, $user);

            throw ValidationException::withMessages([
                'code' => __('auth.two_factor.invalid_code'),
            ]);
        }

        $remember = (bool) $request->session()->pull(TotpTwoFactorService::SESSION_LOGIN_REMEMBER, false);
        $request->session()->forget(TotpTwoFactorService::SESSION_LOGIN_ID);

        Auth::login($user, $remember);
        $request->session()->regenerate();
        $this->authorizationContext->recordPassword($request, $user);
        $this->auditLogger->record('auth.public_login_success', $user, $user, [
            'two_factor' => true,
        ]);

        return redirect()->intended(route('dashboard'));
    }

    private function challengedUser(Request $request): ?User
    {
        $id = $request->session()->get(TotpTwoFactorService::SESSION_LOGIN_ID);

        if (! is_numeric($id)) {
            return null;
        }

        /** @var User|null $user */
        $user = User::query()->find((int) $id);

        if ($user === null || ! $this->twoFactor->hasEnabledTwoFactor($user)) {
            $request->session()->forget([
                TotpTwoFactorService::SESSION_LOGIN_ID,
                TotpTwoFactorService::SESSION_LOGIN_REMEMBER,
            ]);

            return null;
        }

        return $user;
    }
}
