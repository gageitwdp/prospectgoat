<?php

namespace App\Http\Middleware;

use App\Services\Plans\PlanModuleVisibilityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanModuleIsEnabled
{
    public function __construct(private readonly PlanModuleVisibilityService $planModuleVisibility) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isGlobalAdmin()) {
            return $next($request);
        }

        if (! $this->planModuleVisibility->isEnabledForAccount($user->account, $moduleKey)) {
            abort(403, 'This module is not available on your account plan.');
        }

        return $next($request);
    }
}
