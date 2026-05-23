<?php

namespace App\Middleware;

use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Middleware\MiddlewareInterface;

class SessionAuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (auth()->guest()) {
            return redirect('/login');
        }

        $request->setAttribute('user', auth()->user());

        return $next($request);
    }
}
