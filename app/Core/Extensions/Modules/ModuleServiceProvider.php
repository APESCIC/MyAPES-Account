<?php

namespace App\Core\Extensions\Modules;

use Illuminate\Support\ServiceProvider;

/**
 * Base service provider for module packages (#283).
 *
 * Packages register a {@see ModuleManifest} during register() so the Core
 * registries can compose the compatibility matrix before boot listeners run.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    abstract protected function manifest(): ModuleManifest;

    public function register(): void
    {
        $registry = $this->app->make(ModulePackageRegistry::class);

        if (! $registry->has($this->manifest()->slug)) {
            $registry->register($this->manifest());
        }
    }
}
