<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Ensure only super admins can access.
     * Redirects non-super-admins to a forbidden page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if (!$request->user()->is_super_admin) {
            // Logout and show error
            auth()->logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Access denied. Super admin privileges required.',
            ]);
        }

        return $next($request);
    }
}
