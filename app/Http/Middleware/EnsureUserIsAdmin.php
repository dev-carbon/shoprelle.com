<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the whole back office behind the administrator flag.
 *
 * Policies still guard each individual action; this middleware keeps
 * non-administrators from reaching the routes at all.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isAdmin() === true, 403);

        return $next($request);
    }
}
