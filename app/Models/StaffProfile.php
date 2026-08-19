<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'job_title',
    'team',
    'work_phone',
    'photo_path',
])]
class StaffProfile extends Model
{
    public const TEAM_APES_CIC = 'apes-cic';

    public const TEAM_SHELTER_RESCUE = 'shelter-rescue';

    public const TEAM_PET_CARE_CLINIC = 'pet-care-clinic';

    public const TEAM_OPERATIONS = 'operations';

    /**
     * @return list<string>
     */
    public static function teams(): array
    {
        return [
            self::TEAM_APES_CIC,
            self::TEAM_SHELTER_RESCUE,
            self::TEAM_PET_CARE_CLINIC,
            self::TEAM_OPERATIONS,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
