<?php

namespace App\Providers;

use App\Contracts\ModuleLifecycleManager;
use App\Contracts\ModuleNavigationProvider;
use App\Contracts\ModuleRegistry;
use App\Modules\FirstPartyModuleRegistry;
use App\Services\AuthorizationProfile;
use App\Services\DatabaseModuleLifecycleManager;
use App\Services\RegistryModuleNavigationProvider;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            ModuleRegistry::class,
            FirstPartyModuleRegistry::class,
        );
        $this->app->scoped(AuthorizationProfile::class);
        $this->app->bind(
            ModuleLifecycleManager::class,
            DatabaseModuleLifecycleManager::class,
        );
        $this->app->bind(
            ModuleNavigationProvider::class,
            RegistryModuleNavigationProvider::class,
        );
    }
}
