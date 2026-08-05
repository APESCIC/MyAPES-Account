<?php

namespace App\Models;

use App\Services\AuthorizationProfile;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->can(AuthorizationProfile::PERMISSION_STAFF_ACCESS)
            ? $query
            : $query->where('user_id', $user->id);
    }

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
