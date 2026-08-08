<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Cast;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'channel', 'granted', 'policy_version', 'source', 'actor_user_id', 'recorded_at'])]
class ContactConsentEvent extends Model
{
    protected static function booted(): void
    {
        static::updating(static function (): never {
            throw new \LogicException('Consent events are append-only.');
        });
        static::deleting(static function (): never {
            throw new \LogicException('Consent events are append-only.');
        });
    }

    #[Cast]
    protected function casts(): array
    {
        return ['granted' => 'boolean', 'recorded_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
