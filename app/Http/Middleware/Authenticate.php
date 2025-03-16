<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Auth\AuthenticationException;

/**
 * Sanctum Authentication Middleware
 *
 * This middleware is responsible for authenticating requests with Sanctum.
 * It checks if the authentication token is present in the request header.
 * If the token is present, it will be used to authenticate the user.
 * If the token is not present, the request will be considered unauthorized.
 */
class Authenticate extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $token = $request->bearerToken()
            ?? $request->cookie('access_token')
            ?? $request->input('authorization');
            
        if ($token) {
            $request->headers->set('Authorization', "Bearer {$token}");
            $request->headers->set('Accept', 'application/json');
        } else {
            throw new AuthenticationException('Token not provided');
        }
        
        // This is the missing piece - actually authenticate via parent
        $this->authenticate($request, $guards);
        
        return $next($request);
    }
}