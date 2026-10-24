<?php

namespace App\Middleware;

use App\Auth\SessionAuthenticator;
use YasserElgammal\Green\Auth\AuthenticationException;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Middleware\MiddlewareInterface;

class SessionAuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (auth()->resolve($request, app(SessionAuthenticator::class)) === null) {
            throw new AuthenticationException();
        }

        return $next($request);
    }
}
