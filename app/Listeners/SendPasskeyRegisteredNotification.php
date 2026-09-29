<?php

namespace App\Listeners;

use App\Core\Accounts\User;
use App\Notifications\Auth\PasskeyAddedNotification;
use App\Services\AuditLogger;
use Laravel\Passkeys\Events\PasskeyRegistered;

class SendPasskeyRegisteredNotification
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handle(PasskeyRegistered $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $user->notify(new PasskeyAddedNotification);

        $this->auditLogger->record('auth.passkey_registered', $user, $user, [
            'passkey_id' => $event->passkey->id,
            'passkey_name' => $event->passkey->name,
        ]);
    }
}
