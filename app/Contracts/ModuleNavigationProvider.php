<?php

namespace App\Contracts;

use App\Models\User;
use App\Modules\ModuleNavigationItem;
use App\Modules\SubCoreNavigation;

interface ModuleNavigationProvider
{
    /** @return array<int, SubCoreNavigation> */
    public function forUser(User $user): array;

    /** @return array<int, ModuleNavigationItem> */
    public function forSubCore(User $user, string $subCoreKey): array;
}
