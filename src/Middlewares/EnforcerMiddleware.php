<?php

namespace Lauthz\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;
use Lauthz\Exceptions\UnauthorizedException;
use Lauthz\Facades\Enforcer;

/**
 * A basic Enforcer Middleware.
 */
class EnforcerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param mixed                    ...$args
     *
     * @return mixed
     */
    public function handle($request, Closure $next, ...$args)
    {
        if (Auth::guest()) {
            throw new UnauthorizedException();
            // return $next($request);
        }

        $user = Auth::user();
        $identifier = $user->getAuthIdentifier();
        if (method_exists($user, 'getAuthzIdentifier')) {
            $identifier = $user->getAuthzIdentifier();
        }
        $identifier = strval($identifier);

        if (!Enforcer::enforce($identifier, ...$args)) {
            throw new UnauthorizedException();
        }

        return $next($request);
    }
}
