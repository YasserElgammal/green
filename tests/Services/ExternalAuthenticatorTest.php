<?php

namespace Tests\Services;

use Tests\TestCase;
use YasserElgammal\Green\Auth\Authenticator;
use YasserElgammal\Green\Http\Middleware\Authenticate;
use YasserElgammal\Green\Http\{Request, Response};
use YasserElgammal\Green\Routing\Route;

final class ExternalAuthenticatorTest extends TestCase
{
    private function configure(?object $identity, bool $allowed): void
    {
        $adapter = new class($identity) implements Authenticator {
            public function __construct(private ?object $identity) {}
            public function authenticate(Request $request): ?object { return $this->identity; }
        };
        $this->app->router->aliasMiddleware('auth.custom', fn () => new Authenticate($adapter, context: 'external'));
        $this->app->router->registerRoutesFromController(ExternalIdentityController::class);
        authorizer()->define('use-external', fn ($actor) => $actor !== null && $allowed);
    }

    public function testExternalAdapterWorksThroughGenericMiddlewareAndRequestLifecycle(): void
    {
        $identity = (object) ['id' => 'custom-service'];
        $this->configure($identity, true);
        $request = new Request(server: ['REQUEST_URI' => '/external-auth', 'HTTP_ACCEPT' => 'application/json']);
        self::assertSame(200, $this->app->handle($request)->getStatusCode());
        self::assertSame($identity, $request->user());
        self::assertNull(auth()->user());
    }

    public function testMissingExternalIdentityIs401(): void
    {
        $this->configure(null, true);
        self::assertSame(401, $this->app->handle(new Request(server: ['REQUEST_URI' => '/external-auth', 'HTTP_ACCEPT' => 'application/json']))->getStatusCode());
    }

    public function testDeniedExternalIdentityIs403(): void
    {
        $this->configure((object) ['id' => 'custom-service'], false);
        self::assertSame(403, $this->app->handle(new Request(server: ['REQUEST_URI' => '/external-auth', 'HTTP_ACCEPT' => 'application/json']))->getStatusCode());
        self::assertNull(auth()->user());
    }
}

final class ExternalIdentityController
{
    #[Route('GET', '/external-auth', ['auth.custom'])]
    public function show(Request $request): Response
    {
        if ($request->user() !== auth()->user()) {
            throw new \LogicException('Request and Auth identities must match.');
        }
        authorizer()->authorize('use-external', null, $request->user());
        return new Response('external identity accepted');
    }
}
