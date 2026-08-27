<?php

namespace Lauthz\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Lauthz\Exceptions\UnauthorizedException;
use Lauthz\Facades\Enforcer;

/**
 * A HTTP Request Middleware.
 */
class RequestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string                   ...$guards
     *
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        if (Auth::guest()) {
            throw new UnauthorizedException();
        }

        $this->authorize($request, $guards);

        return $next($request);
    }

    /**
     * Determine if the user is authorized in to any of the given guards.
     *
     * @param \Illuminate\Http\Request      $request
     * @param array<int|string, string>     $guards
     *
     * @throws \Lauthz\Exceptions\UnauthorizedException
     */
    protected function authorize(Request $request, array $guards): void
    {
        $user = Auth::user();
        if (!$user) {
            throw new UnauthorizedException();
        }

        $identifier = $user->getAuthIdentifier();
        if (method_exists($user, 'getAuthzIdentifier')) {
            $identifier = $user->getAuthzIdentifier();
        }
        $identifier = strval($identifier);

        if (empty($guards)) {
            if (Enforcer::enforce($identifier, $request->getPathInfo(), $request->method())) {
                return;
            }
        }

        foreach ($guards as $guard) {
            if (Enforcer::guard($guard)->enforce($identifier, $request->getPathInfo(), $request->method())) {
                Enforcer::shouldUse($guard);
                return;
            }
        }

        throw new UnauthorizedException();
    }
}
