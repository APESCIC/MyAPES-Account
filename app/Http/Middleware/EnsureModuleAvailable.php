<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated Prefer {@see EnsurePluginEnabled} / middleware alias `plugin.enabled`.
 *             Kept as a thin wrapper until plugin route moves (#289–#291) finish.
 */
class EnsureModuleAvailable
{
    public function __construct(
        private readonly EnsurePluginEnabled $pluginEnabled,
    ) {}

    public function handle(
        Request $request,
        Closure $next,
        string $subCoreKey,
        string $moduleKey,
    ): Response {
        return $this->pluginEnabled->handle(
            $request,
            $next,
            $subCoreKey,
            $moduleKey,
        );
    }
}
