<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Application skeleton provider (#282).
 *
 * Domain bootstrapping lives on Core* and ExtensionRegistry providers.
 * Plugin policies and public nav contributions live on plugin packages.
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
