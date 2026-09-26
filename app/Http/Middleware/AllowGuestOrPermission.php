<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets guests through (public browsing), while signed-in users keep the
 * normal verified-email and permission checks of the protected area.
 */
class AllowGuestOrPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        return app(EnsureEmailIsVerified::class)->handle(
            $request,
            function (Request $request) use ($next, $permission, $user): Response {
                if (! $user->can($permission)) {
                    throw UnauthorizedException::forPermissions([$permission]);
                }

                return $next($request);
            },
        );
    }
}
