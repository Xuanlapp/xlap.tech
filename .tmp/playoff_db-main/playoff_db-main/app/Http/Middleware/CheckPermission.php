<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        if (Auth::guest()) {
            return redirect()->route('login');
        }

        if (! $request->user()->can($permission)) {
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
} 