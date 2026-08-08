<?php

namespace App\Http\Middleware;

use App\Contracts\ModuleRegistry;
use App\Services\ServiceEntitlement;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureServiceSelected
{
    public function __construct(
        private readonly ServiceEntitlement $entitlement,
        private readonly ModuleRegistry $registry,
    ) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next, string $subCoreKey): Response
    {
        try {
            $service = $this->registry->subCore($subCoreKey);
        } catch (\InvalidArgumentException) {
            abort(404);
        }

        if (! $this->entitlement->allows($request->user(), $subCoreKey, $request)) {
            return redirect()
                ->route('profile.edit')
                ->with('status', "Select {$service->name} in your preferences to continue.");
        }

        return $next($request);
    }
}
