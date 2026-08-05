<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Cast;
use Illuminate\Database\Eloquent\Model;

class AuthorizationState extends Model
{
    public const SINGLETON_ID = 1;

    #[Cast]
    protected function casts(): array
    {
        return [
            'authorization_epoch' => 'integer',
            'cutover_completed_at' => 'datetime',
            'session_cutover_completed_at' => 'datetime',
            'directory_sync_expires_at' => 'datetime',
        ];
    }
}
