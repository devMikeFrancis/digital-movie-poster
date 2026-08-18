<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates an endpoint behind a Sanctum token, but only when the operator has
 * opted in via DMP_API_REQUIRE_TOKEN. The check happens per request rather
 * than at route-registration time so that `php artisan route:cache` does not
 * bake the setting into the compiled routes.
 */
class RequireApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('dmp.api.require_token')) {
            return $next($request);
        }

        return app(Authenticate::class)->handle($request, $next, 'sanctum');
    }
}
