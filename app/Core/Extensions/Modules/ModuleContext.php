<?php

namespace App\Core\Extensions\Modules;

use App\Contracts\ModuleRegistry;
use App\Modules\ModuleInstanceDefinition;
use App\Services\ModuleRouteContext;
use App\Services\ModuleSettingsService;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Request-scoped current module for plugin controllers (#283).
 *
 * Replaces reading `subCoreKey` route defaults directly. Delegates enablement
 * resolution to the existing ModuleRouteContext until plugin packages own routes.
 */
final class ModuleContext
{
    public function __construct(
        private readonly ModulePackageRegistry $modules,
        private readonly ModuleRegistry $registry,
        private readonly ModuleRouteContext $routeContext,
        private readonly Request $request,
    ) {}

    public function slug(): ?string
    {
        $subCoreKey = $this->request->route('subCoreKey');

        return is_string($subCoreKey) ? $subCoreKey : null;
    }

    public function manifest(): ?ModuleManifest
    {
        $slug = $this->slug();

        if ($slug === null || ! $this->modules->has($slug)) {
            return null;
        }

        return $this->modules->module($slug);
    }

    public function routePrefix(): ?string
    {
        return $this->manifest()?->routePrefix;
    }

    /**
     * Resolve the current plugin enablement instance for a plugin slug.
     */
    public function pluginInstance(string $pluginSlug): ModuleInstanceDefinition
    {
        return $this->routeContext->resolve($this->request, $pluginSlug);
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(string $pluginSlug): array
    {
        $slug = $this->slug()
            ?? throw new InvalidArgumentException('No module context on this request.');

        try {
            $this->registry->instance($slug, $pluginSlug);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException("Unknown plugin [{$pluginSlug}] for module [{$slug}].");
        }

        return app(ModuleSettingsService::class)
            ->settings($slug, $pluginSlug) ?? [];
    }
}
