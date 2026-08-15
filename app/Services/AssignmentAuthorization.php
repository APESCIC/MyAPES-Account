<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AssignmentAuthorization
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function authorizeChange(
        Request $request,
        User $actor,
        Model $subject,
        ?string $requiredPermission = null,
    ): void {
        if ($this->allows($actor, $requiredPermission)) {
            return;
        }

        $this->auditLogger->record(
            'authorization.assignment_denied',
            $actor,
            $subject,
            [
                'actor_id' => $actor->id,
                'route_name' => $request->route()?->getName(),
                'method' => $request->method(),
                'reason_code' => 'permission_denied',
            ],
        );

        throw new AuthorizationException;
    }

    public function allows(
        User $actor,
        ?string $requiredPermission = null,
    ): bool {
        $permission = $requiredPermission
            ?? AuthorizationProfile::PERMISSION_STAFF_ACCESS;

        return $actor->can($permission)
            && User::query()
                ->eligibleStaff()
                ->whereKey($actor->id)
                ->exists();
    }
}
