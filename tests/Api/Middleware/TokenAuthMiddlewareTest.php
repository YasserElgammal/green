<?php

namespace Tests\Api\Middleware;

use App\Middleware\TokenAuthMiddleware;
use App\Tables\UserTable;
use Tests\TestCase;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;

class TokenAuthMiddlewareTest extends TestCase
{
    private TokenAuthMiddleware $middleware;
    private UserTable $users;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new TokenAuthMiddleware();
        $this->users = new UserTable();
    }

    public function testValidBearerTokenAuthenticatesRequest(): void
    {
        $this->seed();

        $user = $this->users->fetchByIdOrFail(1);
        $token = auth()->issueToken($user);
        $request = new Request(server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $response = $this->middleware->handle($request, function (Request $request) use ($user) {
            $authenticatedUser = $request->getAttribute('user');

            $this->assertNotNull($authenticatedUser);
            $this->assertSame($user->id, $authenticatedUser->id);
            $this->assertSame($user->email, $authenticatedUser->email);

            return new Response('ok');
        });

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }

    public function testMissingBearerTokenReturnsUnauthorized(): void
    {
        $response = $this->middleware->handle(new Request(), fn () => new Response('ok'));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(['error' => 'Unauthorized'], json_decode($response->getContent(), true));
    }

    public function testInvalidBearerTokenReturnsUnauthorized(): void
    {
        $request = new Request(server: [
            'HTTP_AUTHORIZATION' => 'Bearer invalid-token',
        ]);

        $response = $this->middleware->handle($request, fn () => new Response('ok'));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame(['error' => 'Unauthorized'], json_decode($response->getContent(), true));
    }
}
