<?php

namespace App\Http\Middleware;

use App\Core\Extensions\Plugins\PluginEnablement;
use App\Exceptions\ModuleLifecycleException;
use App\Services\ModuleInstanceLock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate routes when a plugin is disabled for a module (#288).
 *
 * Prefer this over the deprecated {@see EnsureModuleAvailable} alias.
 */
class EnsurePluginEnabled
{
    public function __construct(
        private readonly PluginEnablement $enablement,
        private readonly ModuleInstanceLock $lock,
    ) {}

    public function handle(
        Request $request,
        Closure $next,
        string $module,
        string $plugin,
    ): Response {
        try {
            $this->enablement->assertEnabled($module, $plugin);

            if ($request->isMethodSafe()) {
                return $next($request);
            }

            return $this->lock->run(
                $module,
                $plugin,
                function () use ($request, $next, $module, $plugin): Response {
                    $this->enablement->assertEnabled($module, $plugin);

                    return $next($request);
                },
            );
        } catch (ModuleLifecycleException) {
            abort(404);
        }
    }
}
