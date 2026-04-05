<?php

namespace App\Middleware;

use YasserElgammal\Green\Middleware\MiddlewareInterface;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly string $validUsername = 'user',
        private readonly string $validPassword = 'secret',
    ) {}

    public function handle(Request $request, callable $next): Response
    {
        $unauthorized = new Response('Unauthorized', 401, ['WWW-Authenticate' => 'Basic realm="Green"']);

        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Basic ')) {
            return $unauthorized;
        }

        $decoded = base64_decode(substr($authHeader, 6), strict: true);

        if ($decoded === false || !str_contains($decoded, ':')) {
            return $unauthorized;
        }

        [$username, $password] = explode(':', $decoded, 2);

        if ($username !== $this->validUsername || $password !== $this->validPassword) {
            return $unauthorized;
        }

        return $next($request);
    }
}
