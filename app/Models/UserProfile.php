<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'preferred_name',
    'phone',
    'organization',
    'support_needs',
    'avatar_path',
    'address_line_1',
    'address_line_2',
    'town_city',
    'county',
    'postcode',
    'country',
    'mobile_number',
    'landline_number',
    'whatsapp_number',
    'telegram_username',
])]
class UserProfile extends Model
{
    public function effectiveWhatsappNumber(): ?string
    {
        return $this->whatsapp_number ?: $this->mobile_number;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
