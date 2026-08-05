<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Cast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectoryGroupRoleMapping extends Model
{
    #[Cast]
    protected function casts(): array
    {
        return [
            'is_immutable' => 'boolean',
        ];
    }

    public function directoryGroup(): BelongsTo
    {
        return $this->belongsTo(DirectoryGroup::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
