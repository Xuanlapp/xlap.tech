<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasProductAccess
{
    /**
     * Ensure the current user can open the requested product page.
     */
    public function handle(Request $request, Closure $next, string $productSlug): Response
    {
        $user = $request->user();

        abort_unless($user && $user->canAccessProduct($productSlug), 403);

        return $next($request);
    }
}
