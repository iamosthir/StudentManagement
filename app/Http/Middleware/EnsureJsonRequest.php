<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJsonRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only proceed if the request wants JSON or is an AJAX request
        if (!$request->wantsJson() && !$request->ajax()) {
            abort(404); // This will let it fall through to the SPA catchall route
        }

        return $next($request);
    }
}
