<?php

namespace App\Middleware;

use App\Auth\JwtAuthenticator;
use YasserElgammal\Green\Auth\AuthenticationException;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Middleware\MiddlewareInterface;

class TokenAuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (auth()->resolve($request, app(JwtAuthenticator::class)) === null) {
            throw new AuthenticationException('A valid bearer token is required.');
        }

        return $next($request);
    }
}
