<?php

namespace App\Middleware;

use App\Auth\SessionAuthenticator;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Middleware\MiddlewareInterface;

class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        auth()->resolve($request, app(SessionAuthenticator::class));

        if (auth()->check()) {
            return redirect('/profile');
        }

        return $next($request);
    }
}
