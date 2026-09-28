<?php

namespace App\Contracts;

use App\Core\Accounts\User;
use App\Modules\ModuleAttentionItem;
use App\Modules\ModuleInstanceDefinition;

interface ModuleAttentionProvider
{
    /** @return array<int, ModuleAttentionItem> */
    public function attention(
        ModuleInstanceDefinition $instance,
        User $user,
        int $limit = 6,
    ): array;
}
