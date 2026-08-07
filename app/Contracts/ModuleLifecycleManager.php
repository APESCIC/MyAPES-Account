<?php

namespace App\Contracts;

use App\Models\ModuleInstallation;
use App\Models\User;

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
