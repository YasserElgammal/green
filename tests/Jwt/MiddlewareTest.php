<?php

namespace Tests\Jwt\Web;

use App\Middleware\GuestMiddleware;
use App\Middleware\LocaleMiddleware;
use App\Middleware\SessionAuthMiddleware;
use App\Middleware\TokenAuthMiddleware;
use App\Middleware\TrimStringsMiddleware;
use App\Middleware\ValidateSessionUserMiddleware;
use App\Middleware\ValidationExceptionMiddleware;
use Tests\JwtTestCase;
use YasserElgammal\Green\Http\RedirectResponse;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Http\ValidationException;
use YasserElgammal\Green\Auth\AuthenticationException;

class MiddlewareTest extends JwtTestCase
{
    public function testTokenMiddlewareRejectsMissingAndInvalidTokens(): void
    {
        $middleware = new TokenAuthMiddleware();
        try {
            $middleware->handle(new Request(), fn () => new Response('unreachable'));
            $this->fail('Missing credentials should fail authentication.');
        } catch (AuthenticationException $e) {
            $this->assertSame(401, $e->getStatusCode());
        }

        $this->expectException(AuthenticationException::class);
        $middleware->handle(new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer invalid']), fn () => new Response('unreachable'));
    }

    public function testTokenMiddlewareAttachesTheAuthenticatedUser(): void
    {
        $user = $this->createUser();
        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer ' . app(\App\Services\JwtTokenService::class)->issueToken($user)]);
        $response = (new TokenAuthMiddleware())->handle($request, function (Request $request) use ($user): Response {
            $this->assertSame($user->id, $request->getAttribute('user')->id);
            return new Response('ok');
        });
        $this->assertSame('ok', $response->getContent());
    }


}
