<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Ensure the current user can access admin pages.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless((bool) $request->user()?->is_admin, 403);

        return $next($request);
    }
}
