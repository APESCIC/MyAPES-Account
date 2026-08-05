<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleSource extends Model
{
    public const SOURCE_SYSTEM = 'system';

    public const SOURCE_DIRECTORY = 'directory';

    public const SOURCE_LOCAL = 'local';

    public const SOURCE_LEGACY_COMPATIBILITY = 'legacy-compatibility';

    /**
     * @return array<int, string>
     */
    public static function sources(): array
    {
        return [
            self::SOURCE_SYSTEM,
            self::SOURCE_DIRECTORY,
            self::SOURCE_LOCAL,
            self::SOURCE_LEGACY_COMPATIBILITY,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function directoryGroup(): BelongsTo
    {
        return $this->belongsTo(DirectoryGroup::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
