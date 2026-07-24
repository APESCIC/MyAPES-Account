<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function record(
        string $event,
        ?User $actor = null,
        ?Model $auditable = null,
        array $context = []
    ): void {
        AuditLog::create([
            'user_id' => $actor?->id,
            'event' => $event,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'context' => $context,
        ]);
    }
}
