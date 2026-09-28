<?php

namespace App\Core\Providers;

use App\Core\Eloquent\MorphMap;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        MorphMap::enforce();
    }
}
