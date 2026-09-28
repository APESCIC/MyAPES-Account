<?php

namespace App\Contracts;

use App\Core\Accounts\User;
use App\Core\Extensions\Models\ModuleInstallation;

interface ModuleLifecycleManager
{
    public function install(
        User $actor,
        string $subCoreKey,
        string $moduleKey,
    ): ModuleInstallation;

    public function enable(
        User $actor,
        string $subCoreKey,
        string $moduleKey,
        int $expectedVersion,
    ): ModuleInstallation;

    public function disable(
        User $actor,
        string $subCoreKey,
        string $moduleKey,
        int $expectedVersion,
    ): ModuleInstallation;
}
