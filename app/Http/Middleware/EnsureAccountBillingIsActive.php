<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountBillingIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isGlobalAdmin()) {
            return $next($request);
        }

        $account = $user?->account;

        if ($account && $account->requiresBillingSetup()) {
            return redirect()->route('billing.collect');
        }

        return $next($request);
    }
}