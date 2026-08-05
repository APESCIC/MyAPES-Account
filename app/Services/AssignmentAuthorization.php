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
    ): void {
        if ($this->allows($actor)) {
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

    public function allows(User $actor): bool
    {
        return $actor->can(
            AuthorizationProfile::PERMISSION_STAFF_ACCESS,
        )
            && User::query()
                ->eligibleStaff()
                ->whereKey($actor->id)
                ->exists();
    }
}
