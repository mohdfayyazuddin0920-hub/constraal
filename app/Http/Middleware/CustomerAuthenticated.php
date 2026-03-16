<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CustomerAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('account.customer.login');
        }

        // If user has 2FA pending verification, don't allow portal access
        if ($request->session()->has('2fa_user_id')) {
            return redirect()->route('account.customer.2fa.verify');
        }

        return $next($request);
    }
}
