<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class JWTRoleAuth extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param null $role
     * @return mixed
     * @throws AuthorizationException
     */
    public function handle(Request $request, Closure $next, $role = null)
    {
        try {
            $tokenRole = $this->auth->parseToken()->getClaim('role');
        } catch (JWTException $e) {
            return $next($request);
        }
        if ($tokenRole !== $role) {
            throw new AuthorizationException;
        }

        return $next($request);
    }
}
