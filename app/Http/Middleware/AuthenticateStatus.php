<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;

class AuthenticateStatus
{

    /**
     * The authentication factory instance
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected Auth $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $guard
     * @return mixed
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next, string $guard)
    {
        if ($this->auth->guard($guard)->user()->status !== 1) {
            throw new AuthorizationException;
        }
        return $next($request);
    }
}
