<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\PendingFirstLoginChaseNotification;
use DomainException;

class PendingFirstLoginChaseService
{
    public function __construct(
        private readonly AuthorizationMutationService $mutations,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function canChase(User $actor, User $target): bool
    {
        return $this->refusalMessage($actor, $target) === null;
    }

    public function chase(User $actor, User $target): void
    {
        $refusal = $this->refusalMessage($actor, $target);

        if ($refusal !== null) {
            throw new DomainException($refusal);
        }

        $target->notify(new PendingFirstLoginChaseNotification($actor));

        $this->auditLogger->record(
            'auth.pending_first_login_chase',
            $actor,
            $target,
            [
                'target_user_id' => $target->id,
                'action' => 'chase_pending_first_login',
                'login_path' => 'staff_login',
            ],
        );
    }

    public function refusalMessage(User $actor, User $target): ?string
    {
        if (! $this->mutations->canManageTarget($actor, $target)) {
            return 'You cannot chase first login for this account.';
        }

        if ($target->isLocalPasswordIdentity()) {
            return 'Local public accounts use the public password reset path, not Staff Login chase.';
        }

        if (! $target->isPendingFirstLogin()) {
            return 'Only directory accounts pending first login can be chased to Staff Login.';
        }

        return null;
    }
}
