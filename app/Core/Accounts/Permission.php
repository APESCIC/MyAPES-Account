<?php

namespace App\Core\Accounts;

use Illuminate\Database\Eloquent\Attributes\Cast;

class Permission extends \Spatie\Permission\Models\Permission
{
    protected $guarded = ['is_code_owned'];

    #[Cast]
    protected function casts(): array
    {
        return [
            'is_code_owned' => 'boolean',
        ];
    }
}
