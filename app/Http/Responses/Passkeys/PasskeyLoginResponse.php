<?php

namespace App\Http\Responses\Passkeys;

use App\Core\Accounts\User;
use App\Services\AuditLogger;
use App\Services\SessionAuthorizationContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class PasskeyLoginResponse implements PasskeyLoginResponseContract
{
    public function __construct(
        private readonly SessionAuthorizationContext $authorizationContext,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        $user = $request->user();

        if ($user instanceof User) {
            $this->authorizationContext->recordPassword($request, $user);
            $this->auditLogger->record('auth.passkey_login_success', $user, $user);
        }

        $redirect = redirect()->intended(config('passkeys.redirect', '/dashboard'))->getTargetUrl();

        if ($request->wantsJson()) {
            return new JsonResponse([
                'redirect' => $redirect,
            ], 200);
        }

        return redirect()->to($redirect);
    }
}
