<?php

namespace App\Services;

use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LocalPublicPasswordResetService
{
    public function __construct(
        private readonly AuthorizationProfile $profile,
        private readonly AuthorizationMutationService $mutations,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function canReset(User $actor, User $target): bool
    {
        return $this->refusalMessage($actor, $target) === null;
    }

    public function reset(User $actor, User $target): string
    {
        $refusal = $this->refusalMessage($actor, $target);

        if ($refusal !== null) {
            throw new DomainException($refusal);
        }

        $temporaryPassword = Str::password(20);

        DB::transaction(function () use ($actor, $target, $temporaryPassword): void {
            $target->forceFill([
                'password' => $temporaryPassword,
                'authorization_epoch' => (int) $target->authorization_epoch + 1,
            ]);
            $target->setRememberToken(Str::random(60));
            $target->save();

            $this->auditLogger->record(
                'auth.local_public_password_reset',
                $actor,
                $target,
                [
                    'target_user_id' => $target->id,
                    'action' => 'reset_local_public_password',
                ],
            );
        });

        return $temporaryPassword;
    }

    public function refusalMessage(User $actor, User $target): ?string
    {
        if (! $this->mutations->canManageTarget($actor, $target)) {
            return 'You cannot reset the password for this account.';
        }

        if ($target->isPendingFirstLogin()) {
            return 'Pending first-login directory accounts stay on Cloudron.';
        }

        if (
            ! $target->isLocalPasswordIdentity()
            || $this->profile->hasDirectoryProtectedEligibility($target)
        ) {
            return 'Only local public accounts can have their password reset here. Directory and Cloudron accounts stay on Cloudron.';
        }

        return null;
    }
}
