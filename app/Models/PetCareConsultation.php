<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pet_profile_id',
    'user_id',
    'assigned_to',
    'subject',
    'status',
    'notes',
    'scheduled_for',
    'closed_at',
])]
class PetCareConsultation extends Model
{
    protected function casts(): array
    {
        return [
            'scheduled_for' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function petProfile(): BelongsTo
    {
        return $this->belongsTo(PetProfile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
