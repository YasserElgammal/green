<?php

namespace App\Middleware;

use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Middleware\MiddlewareInterface;

class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (auth()->check()) {
            return redirect('/profile');
        }

        return $next($request);
    }
}
