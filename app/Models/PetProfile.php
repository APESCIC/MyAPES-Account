<?php

namespace App\Models;

use App\Services\AuthorizationProfile;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'service_domain',
    'name',
    'species',
    'age_years',
    'sex',
    'neutering_status',
    'health_issues',
    'photo_path',
])]
class PetProfile extends Model
{
    public const DOMAIN_SHELTER = 'shelter';

    public const DOMAIN_PETCARE = 'petcare';

    public function scopeVisibleTo(
        Builder $query,
        User $user,
        ?string $serviceDomain = null,
    ): Builder {
        if ($serviceDomain === self::DOMAIN_SHELTER) {
            if ($user->can('shelter-rescue.pet-profiles.view-all')) {
                return $query;
            }

            return $user->can('shelter-rescue.pet-profiles.view-own')
                ? $query->where('user_id', $user->id)
                : $query->whereRaw('1 = 0');
        }

        return $user->can(AuthorizationProfile::PERMISSION_STAFF_ACCESS)
            ? $query
            : $query->where('user_id', $user->id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shelterCases(): HasMany
    {
        return $this->hasMany(ShelterCase::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(PetCareConsultation::class);
    }
}
