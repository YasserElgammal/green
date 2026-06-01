<?php

namespace App\Middleware;

use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Middleware\MiddlewareInterface;

class ValidateSessionUserMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (session()->get('user_id')) {
            auth()->check();
        }

        return $next($request);
    }
}
