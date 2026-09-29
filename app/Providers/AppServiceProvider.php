<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Password::defaults(static function (): Password {
            return Password::min(12)->uncompromised();
        });
    }
}
