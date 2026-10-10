<?php

namespace App\Middleware;

use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Middleware\MiddlewareInterface;

class TokenAuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return api()->error('A valid bearer token is required.', [], 401);
        }

        $user = auth()->resolveFromJwt(substr($header, 7));
        if (!$user) {
            return api()->error('The bearer token is invalid or expired.', [], 401);
        }

        $request->setAttribute('user', $user);

        return $next($request);
    }
}
