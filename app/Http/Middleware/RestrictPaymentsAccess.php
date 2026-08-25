<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictPaymentsAccess
{
    /**
     * Limit users with the "payments" role to the payments module only.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || !$user->hasRole('payments')) {
            return $next($request);
        }

        if ($request->routeIs('payments.index')) {
            return $next($request);
        }

        $allowedRoutes = [
            'payments.store',
            'payments.edit',
            'payments.update',
            'payments.destroy',
            'payments.image',
            'payments.excel',
        ];

        if ($request->routeIs(...$allowedRoutes)) {
            return $next($request);
        }

        return redirect()->route('payments.index');
    }
}
