<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class PublicAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.public-login');
    }

    public function login(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $auditLogger->record('auth.public_login_failed', null, null, [
                'email' => $credentials['email'],
            ]);

            return back()
                ->withErrors(['email' => 'The provided credentials do not match our records.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = $request->user();
        if ($user->isStaff()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $auditLogger->record('auth.public_login_blocked_for_staff', $user, $user);

            return redirect()
                ->route('staff.login')
                ->withErrors(['email' => 'Staff accounts must sign in using Staff Login.']);
        }

        $auditLogger->record('auth.public_login_success', $user, $user);

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister(): View
    {
        return view('auth.public-register');
    }

    public function register(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => User::ROLE_SERVICE_USER,
            'email_verified_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $auditLogger->record('auth.public_registration_success', $user, $user);

        return redirect()->route('dashboard');
    }

    public function localStaffLogin(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        if (! app()->environment(['local', 'testing'])) {
            abort(404);
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $auditLogger->record('auth.local_staff_login_failed', null, null, [
                'email' => $credentials['email'],
            ]);

            return back()
                ->withErrors(['email' => 'The provided credentials do not match our records.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = $request->user();
        if (! $user->isStaff()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $auditLogger->record('auth.local_staff_login_denied_for_non_staff', $user, $user);

            return redirect()
                ->route('staff.login')
                ->withErrors(['email' => 'This local staff login is only for staff/admin accounts.']);
        }

        $auditLogger->record('auth.local_staff_login_success', $user, $user, [
            'role' => $user->role,
        ]);

        return redirect()->intended(route('dashboard'));
    }
}
