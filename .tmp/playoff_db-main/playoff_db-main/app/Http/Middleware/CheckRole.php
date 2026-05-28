<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
    {
        if (Auth::guest()) {
            return redirect()->route('login');
        }

        if (! $request->user()->hasRole($role)) {
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
}
