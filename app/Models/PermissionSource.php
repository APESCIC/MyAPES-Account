<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionSource extends Model
{
    public const SOURCE_SYSTEM = 'system';

    public const SOURCE_LOCAL = 'local';

    /**
     * @return array<int, string>
     */
    public static function sources(): array
    {
        return [
            self::SOURCE_SYSTEM,
            self::SOURCE_LOCAL,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
