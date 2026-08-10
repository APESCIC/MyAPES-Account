<?php

namespace App\Services;

use App\Contracts\ModuleRegistry;
use App\Modules\ModuleInstanceDefinition;
use Illuminate\Http\Request;
use InvalidArgumentException;

final readonly class ModuleRouteContext
{
    public function __construct(
        private ModuleRegistry $registry,
    ) {}

    public function resolve(
        Request $request,
        string $expectedModuleKey,
    ): ModuleInstanceDefinition {
        $subCoreKey = $request->route('subCoreKey');
        $moduleKey = $request->route('moduleKey');

        abort_unless(
            is_string($subCoreKey)
                && is_string($moduleKey)
                && $moduleKey === $expectedModuleKey,
            404,
        );

        try {
            $instance = $this->registry->instance($subCoreKey, $moduleKey);
        } catch (InvalidArgumentException) {
            abort(404);
        }

        abort_unless(
            $instance->isShipped()
                && isset($instance->module->navigation[$subCoreKey]),
            404,
        );

        return $instance;
    }

    public function permissionPrefix(ModuleInstanceDefinition $instance): string
    {
        return "{$instance->subCore->key}.{$instance->module->key}.";
    }

    public function indexRouteName(ModuleInstanceDefinition $instance): string
    {
        return $instance->module->navigation[$instance->subCore->key]->routeName;
    }

    public function showRouteName(ModuleInstanceDefinition $instance): string
    {
        $indexRoute = $this->indexRouteName($instance);

        abort_unless(str_ends_with($indexRoute, '.index'), 404);

        return substr($indexRoute, 0, -strlen('.index')).'.show';
    }
}
