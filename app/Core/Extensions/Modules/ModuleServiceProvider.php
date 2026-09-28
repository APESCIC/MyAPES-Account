<?php

namespace App\Core\Extensions\Modules;

use Illuminate\Support\ServiceProvider;

/**
 * Base service provider for module packages (#283 / #284–#286).
 *
 * Packages register a {@see ModuleManifest} during register() so the Core
 * registries can compose the compatibility matrix before boot listeners run.
 * Optional package assets (views, config) load from the module folder without
 * moving plugin controllers or models.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    private ?ModuleManifest $resolvedManifest = null;

    abstract protected function manifest(): ModuleManifest;

    /**
     * Absolute path to the module package root (parent of src/).
     */
    protected function packagePath(): string
    {
        $reflection = new \ReflectionClass($this);

        return dirname($reflection->getFileName(), 2);
    }

    public function register(): void
    {
        $manifest = $this->resolvedManifest();
        $registry = $this->app->make(ModulePackageRegistry::class);

        if (! $registry->has($manifest->slug)) {
            $registry->register($manifest);
        }

        $configPath = $this->packagePath().'/config/plugin-defaults.php';

        if (is_file($configPath)) {
            $this->mergeConfigFrom($configPath, 'modules.'.$manifest->slug.'.plugin_defaults');
        }
    }

    public function boot(): void
    {
        $manifest = $this->resolvedManifest();
        $viewsPath = $this->packagePath().'/resources/views';

        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, $manifest->slug);
        }
    }

    protected function resolvedManifest(): ModuleManifest
    {
        if ($this->resolvedManifest !== null) {
            return $this->resolvedManifest;
        }

        $path = $this->packagePath().'/module.php';
        /** @var ModuleManifest $manifest */
        $manifest = is_file($path)
            ? require $path
            : $this->manifest();

        if (! $manifest instanceof ModuleManifest) {
            $manifest = $this->manifest();
        }

        return $this->resolvedManifest = $manifest;
    }
}
