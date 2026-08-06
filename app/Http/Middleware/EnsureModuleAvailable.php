<?php

namespace App\Http\Middleware;

use App\Exceptions\ModuleLifecycleException;
use App\Services\ModuleInstanceLock;
use App\Services\ModuleState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAvailable
{
    public function __construct(
        private readonly ModuleState $state,
        private readonly ModuleInstanceLock $lock,
    ) {}

    public function handle(
        Request $request,
        Closure $next,
        string $subCoreKey,
        string $moduleKey,
    ): Response {
        try {
            $this->state->assertEnabled($subCoreKey, $moduleKey);

            if ($request->isMethodSafe()) {
                return $next($request);
            }

            return $this->lock->run(
                $subCoreKey,
                $moduleKey,
                function () use (
                    $request,
                    $next,
                    $subCoreKey,
                    $moduleKey,
                ): Response {
                    $this->state->assertEnabled($subCoreKey, $moduleKey);

                    return $next($request);
                },
            );
        } catch (ModuleLifecycleException $exception) {
            if ($exception->reason === 'instance_busy') {
                abort(409);
            }

            abort(404);
        }
    }
}
