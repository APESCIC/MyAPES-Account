<?php

namespace App\Listeners;

use App\Core\Accounts\User;
use App\Notifications\Auth\PasskeyRemovedNotification;
use App\Services\AuditLogger;
use Laravel\Passkeys\Events\PasskeyDeleted;

class SendPasskeyDeletedNotification
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(PasskeyDeleted $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $user->notify(new PasskeyRemovedNotification);

        $this->auditLogger->record('auth.passkey_deleted', $user, $user, [
            'passkey_id' => $event->passkey->id,
            'passkey_name' => $event->passkey->name,
        ]);
    }
}
