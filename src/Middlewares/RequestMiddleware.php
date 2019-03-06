<?php

namespace Lauthz\Middlewares;

use Closure;
use Illuminate\Support\Facades\Auth;
use Lauthz\Exceptions\UnauthorizedException;
use Lauthz\Facades\Enforcer;

/**
 * A HTTP Request Middleware.
 */
class RequestMiddleware
{
    /**
     * The authentication factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param \Illuminate\Contracts\Auth\Factory $auth
     *
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param mixed                    ...$args
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::guest()) {
            throw new UnauthorizedException();
        }

        $user = Auth::user();
        $identifier = $user->getAuthIdentifier();
        if (method_exists($user, 'getAuthzIdentifier')) {
            $identifier = $user->getAuthzIdentifier();
        }

        if (!Enforcer::enforce($identifier, $request->getPathInfo(), $request->method())) {
            throw new UnauthorizedException();
        }

        return $next($request);
    }
}
