<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AuditLogger;
use App\Services\MaintenanceResponseFactory;
use Closure;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMaintenanceRecoveryAccess
{
    public function __construct(
        private readonly MaintenanceMode $maintenanceMode,
        private readonly MaintenanceResponseFactory $responses,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->maintenanceMode->active()
            && ! $request->user()?->can('admin.maintenance.manage')) {
            $actor = $request->user();

            if ($actor instanceof User) {
                $this->audit->record(
                    'maintenance.recovery_access_denied',
                    $actor,
                    context: [
                        'action' => 'access_recovery',
                        'method' => $request->method(),
                        'reason_code' => 'permission_denied',
                        'route_name' => $request->route()?->getName(),
                    ],
                );
            }

            return $this->responses->make($request);
        }

        return $next($request);
    }
}
