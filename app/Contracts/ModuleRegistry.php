<?php

namespace App\Contracts;

use App\Modules\ModuleDefinition;
use App\Modules\ModuleInstanceDefinition;
use App\Modules\ModulePermissionDescriptor;
use App\Modules\SubCoreDefinition;

interface ModuleRegistry
{
    /** @return array<string, SubCoreDefinition> */
    public function subCores(): array;

    /** @return array<string, ModuleDefinition> */
    public function modules(): array;

    /** @return array<int, ModuleInstanceDefinition> */
    public function matrix(): array;

    /** @return array<string, ModuleInstanceDefinition> */
    public function shippedInstances(): array;

    /** @return array<int, ModulePermissionDescriptor> */
    public function permissions(): array;

    public function subCore(string $key): SubCoreDefinition;

    public function module(string $key): ModuleDefinition;

    public function instance(
        string $subCoreKey,
        string $moduleKey,
    ): ModuleInstanceDefinition;

    public function recognizesPermission(string $permission): bool;

    public function permission(string $permission): ?ModulePermissionDescriptor;
}
