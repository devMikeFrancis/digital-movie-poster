<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the endpoints that write, shell out, or return credentials.
 *
 * Authentication can arrive two ways, both handled by the sanctum guard:
 * a session cookie from the admin UI, or a bearer token from an integration.
 *
 * The check happens per request rather than at route-registration time so that
 * `php artisan route:cache` does not bake the setting into the compiled routes.
 */
class RequireAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('dmp.auth.required')) {
            return $next($request);
        }

        // The sanctum guard checks the web session first (the admin UI) and
        // then falls back to a bearer token (an integration). Resolving the
        // default guard here would only ever see the session.
        if (! $request->user('sanctum')) {
            abort(401, 'Authentication required.');
        }

        return $next($request);
    }
}
