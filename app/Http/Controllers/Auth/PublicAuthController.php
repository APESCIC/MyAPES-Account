<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Database\Seeders\LocalQaSeeder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class PublicAuthController extends Controller
{
    public function showLogin(Request $request, AuditLogger $auditLogger): View|RedirectResponse
    {
        if (app()->environment(['local', 'testing'])) {
            $user = $this->requireQaUserByRole(User::ROLE_SERVICE_USER);

            Auth::login($user, true);
            $request->session()->regenerate();
            $auditLogger->record('auth.qa_public_auto_login', $user, $user, [
                'entry' => 'public.login',
            ]);

            return redirect()->intended(route('dashboard'));
        }

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

    public function qaSwitchRole(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        if (! app()->environment(['local', 'testing'])) {
            abort(404);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:service_user,staff,admin'],
        ]);

        /** @var User|null $currentUser */
        $currentUser = $request->user();
        $targetUser = $this->requireQaUserByRole($validated['role']);

        if ($currentUser !== null) {
            Auth::logout();
        }

        Auth::login($targetUser, true);
        $request->session()->regenerate();

        $auditLogger->record('auth.qa_role_switch', $currentUser, $targetUser, [
            'from_role' => $currentUser?->role,
            'to_role' => $targetUser->role,
            'target_email' => $targetUser->email,
        ]);

        $roleLabel = match ($targetUser->role) {
            User::ROLE_SERVICE_USER => 'public service user',
            User::ROLE_STAFF => 'staff user',
            User::ROLE_ADMIN => 'admin user',
            default => 'user',
        };

        return redirect()
            ->route('dashboard')
            ->with('status', "Switched to QA {$roleLabel} ({$targetUser->email}).");
    }

    private function requireQaUserByRole(string $role): User
    {
        $email = match ($role) {
            User::ROLE_SERVICE_USER => LocalQaSeeder::SERVICE_USER_EMAIL,
            User::ROLE_STAFF => LocalQaSeeder::STAFF_EMAIL,
            User::ROLE_ADMIN => LocalQaSeeder::ADMIN_EMAIL,
            default => throw new RuntimeException("Unsupported QA role [{$role}]."),
        };

        /** @var User|null $user */
        $user = User::query()
            ->where('email', $email)
            ->where('role', $role)
            ->first();

        if ($user === null) {
            throw new RuntimeException(
                "Seeded QA {$role} account is missing. Run php artisan db:seed in local/testing."
            );
        }

        return $user;
    }
}
