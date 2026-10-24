<?php

namespace App\Middleware;

use App\Auth\SessionAuthenticator;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Middleware\MiddlewareInterface;

class ValidateSessionUserMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (!str_starts_with($request->getPath(), '/api')) {
            auth()->resolve($request, app(SessionAuthenticator::class));
        }

        return $next($request);
    }
}
