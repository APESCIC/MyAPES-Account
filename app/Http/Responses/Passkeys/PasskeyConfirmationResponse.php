<?php

namespace App\Http\Responses\Passkeys;

use App\Core\Accounts\User;
use App\Services\SessionAuthorizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Contracts\PasskeyConfirmationResponse as PasskeyConfirmationResponseContract;
use Symfony\Component\HttpFoundation\Response;

class PasskeyConfirmationResponse implements PasskeyConfirmationResponseContract
{
    public function __construct(
        private readonly SessionAuthorizationContext $authorizationContext,
    ) {}

    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $request->session()->regenerate();
            $this->authorizationContext->recordPassword($request, $user);
            $request->session()->passwordConfirmed();
        }

        $redirect = $this->intendedUrl($request);

        if ($request->wantsJson()) {
            return new JsonResponse([
                'redirect' => $redirect,
            ], 200);
        }

        return redirect()->to($redirect);
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
        $emailChangePath = parse_url(route('profile.email.change'), PHP_URL_PATH);
        $passkeyOptionsPath = parse_url(route('passkey.registration-options'), PHP_URL_PATH);
        $passkeyStorePath = parse_url(route('passkey.store'), PHP_URL_PATH);

        if (is_string($passwordUpdatePath) && $intendedPath === $passwordUpdatePath) {
            return route('profile.edit').'#change-password';
        }

        if (is_string($usernameUpdatePath) && $intendedPath === $usernameUpdatePath) {
            return route('profile.edit').'#change-username';
        }

        if (is_string($emailChangePath) && $intendedPath === $emailChangePath) {
            return route('profile.edit').'#change-email';
        }

        if (
            (is_string($passkeyOptionsPath) && $intendedPath === $passkeyOptionsPath)
            || (is_string($passkeyStorePath) && $intendedPath === $passkeyStorePath)
            || (is_string($intendedPath) && str_starts_with($intendedPath, '/user/passkeys'))
        ) {
            return route('profile.edit').'#passkeys';
        }

        return $intended;
    }
}
