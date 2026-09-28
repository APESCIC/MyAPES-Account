<?php

namespace App\Core\Extensions\Plugins;

use Illuminate\Support\ServiceProvider;

/**
 * Base service provider for plugin packages (#287).
 */
abstract class PluginServiceProvider extends ServiceProvider
{
    abstract protected function manifest(): PluginManifest;

    public function register(): void
    {
        $registry = $this->app->make(PluginRegistry::class);

        if (! $registry->has($this->manifest()->slug)) {
            $registry->register($this->manifest());
        }
    }

    public function boot(): void
    {
        $manifest = $this->manifest();

        if ($manifest->migrationsPath !== null && is_dir($manifest->migrationsPath)) {
            $this->loadMigrationsFrom($manifest->migrationsPath);
        }
    }
}
