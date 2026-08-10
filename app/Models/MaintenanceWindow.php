<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'message',
    'planned_end_at',
    'state',
    'active_guard',
    'initiated_by',
    'deactivation_requested_by',
    'ended_by',
    'activated_at',
    'deactivation_requested_at',
    'deactivated_at',
    'failure_code',
    'failure_summary',
    'failure_at',
])]
class MaintenanceWindow extends Model
{
    public const STATE_PENDING = 'pending';

    public const STATE_ACTIVE = 'active';

    public const STATE_DEACTIVATION_PENDING = 'deactivation_pending';

    public const STATE_ENDED = 'ended';

    public const STATE_ACTIVATION_FAILED = 'activation_failed';

    public const ACTIVE_GUARD = 'maintenance';

    protected function casts(): array
    {
        return [
            'planned_end_at' => 'datetime',
            'activated_at' => 'datetime',
            'deactivation_requested_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'failure_at' => 'datetime',
        ];
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function deactivationRequester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deactivation_requested_by');
    }

    public function endingActor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by');
    }
}
