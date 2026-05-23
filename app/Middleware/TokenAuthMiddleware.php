<?php

namespace App\Middleware;

use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Middleware\MiddlewareInterface;

class TokenAuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return api_error('Unauthorized.', [
                'authorization' => ['A valid bearer token is required.'],
            ], 401);
        }

        $token = substr($authHeader, 7);
        $user = auth()->resolveFromJwt($token);

        if (!$user) {
            return api_error('Unauthorized.', [
                'authorization' => ['The bearer token is invalid or expired.'],
            ], 401);
        }

        $request->setAttribute('user', $user);

        return $next($request);
    }
}
