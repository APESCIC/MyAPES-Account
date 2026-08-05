<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Cast;

class Role extends \Spatie\Permission\Models\Role
{
    protected $guarded = ['is_protected'];

    public static function bootHasPermissions(): void
    {
        // Database policy must evaluate the role before any pivot is detached.
    }

    #[Cast]
    protected function casts(): array
    {
        return [
            'is_protected' => 'boolean',
        ];
    }
}
