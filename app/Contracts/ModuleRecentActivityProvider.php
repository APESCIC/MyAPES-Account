<?php

namespace App\Contracts;

use App\Models\User;
use App\Modules\ModuleInstanceDefinition;
use App\Modules\ModuleRecentActivityItem;

interface ModuleRecentActivityProvider
{
    /** @return array<int, ModuleRecentActivityItem> */
    public function recent(
        ModuleInstanceDefinition $instance,
        User $user,
        int $limit = 5,
    ): array;
}
