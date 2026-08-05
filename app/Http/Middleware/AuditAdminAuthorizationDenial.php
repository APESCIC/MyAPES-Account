<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditAdminAuthorizationDenial
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
        } catch (AuthorizationException $exception) {
            $this->audit($request);

            throw $exception;
        }

        if ($response->getStatusCode() === Response::HTTP_FORBIDDEN) {
            $this->audit($request);
        }

        return $response;
    }

    private function audit(Request $request): void
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            return;
        }

        $this->auditLogger->record(
            'authorization.admin_denied',
            $actor,
            null,
            [
                'actor_id' => $actor->id,
                'route_name' => $request->route()?->getName(),
                'method' => $request->method(),
                'reason_code' => 'permission_denied',
            ],
        );
    }
}
