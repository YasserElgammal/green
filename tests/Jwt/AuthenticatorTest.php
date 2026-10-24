<?php

declare(strict_types=1);

namespace Tests\Jwt\Services;

use App\Auth\JwtAuthenticator;
use App\Auth\SessionIdentityKey;
use App\Auth\SessionAuthenticator;
use App\Middleware\TokenAuthMiddleware;
use App\Middleware\SessionAuthMiddleware;
use Tests\JwtTestCase;
use YasserElgammal\Green\Application;
use YasserElgammal\Green\Auth\AuthenticationException;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\AuthJwt\JwtConfig;
use YasserElgammal\Green\AuthJwt\JwtService;

final class AuthenticatorTest extends JwtTestCase
{
    public function testJwtServiceRejectsAnEmptySecret(): void
    {
        $app = new Application(
            configOverrides: ['auth' => ['jwt' => ['enabled' => true]], 'jwt' => ['secret' => '', 'verification_key' => '', 'signing_key' => '']],
            basePath: BASE_PATH,
        );

        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessage('JWT_SECRET must be configured');
            $app->make(JwtService::class);
        } finally {
            $app->make(\YasserElgammal\Green\ErrorHandling\GreenErrorKernel::class)->unregister();
            $GLOBALS['__green_app'] = $this->app;
        }
    }

    public function testJwtAuthenticatorAcceptsValidTokenAndRejectsMalformedOrUnknownIdentity(): void
    {
        $user = $this->createUser();
        $authenticator = app(JwtAuthenticator::class);
        $valid = new Request(server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . app(\App\Services\JwtTokenService::class)->issueToken($user),
        ]);

        self::assertSame($user->id, $authenticator->authenticate($valid)?->id);
        self::assertNull($authenticator->authenticate(new Request()));

        foreach (['Basic value', 'Bearer', 'Bearer two parts'] as $header) {
            try {
                $authenticator->authenticate(new Request(server: ['HTTP_AUTHORIZATION' => $header]));
                self::fail('Malformed bearer credentials should fail.');
            } catch (AuthenticationException $e) {
                self::assertSame(401, $e->getStatusCode());
            }
        }

        $unknown = app(JwtService::class)->encode([
            'sub' => 999,
            'token_type' => 'access',
            'iss' => 'green-tests',
            'aud' => 'green-test-clients',
        ]);
        $this->expectException(AuthenticationException::class);
        $authenticator->authenticate(new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $unknown]));
    }

    public function testExpiredAndInvalidSignatureTokensFail(): void
    {
        $user = $this->createUser();
        $expired = (new JwtService(new JwtConfig([
            'secret' => config('jwt.secret'),
            'ttl' => -1,
        ])))->encode(['sub' => $user->id, 'token_type' => 'access']);
        $invalidSignature = (new JwtService(new JwtConfig([
            'secret' => 'another-secret-that-is-at-least-thirty-two-characters',
        ])))->encode(['sub' => $user->id, 'token_type' => 'access']);

        foreach ([$expired, $invalidSignature] as $token) {
            try {
                app(JwtAuthenticator::class)->authenticate(new Request(server: [
                    'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                ]));
                self::fail('Invalid JWT should fail.');
            } catch (AuthenticationException $e) {
                self::assertSame(401, $e->getStatusCode());
            }
        }
    }

    public function testInvalidBearerTokenDoesNotFallBackToLoggedInSession(): void
    {
        auth_service()->login($this->createUser());

        $this->expectException(AuthenticationException::class);
        (new TokenAuthMiddleware())->handle(
            new Request(server: ['REQUEST_URI' => '/api/profile', 'HTTP_AUTHORIZATION' => 'Bearer invalid']),
            fn (): Response => new Response('unreachable'),
        );
    }

    public function testFutureNbfWrongIssuerAudienceAndInvalidSubjectsFail(): void
    {
        $user = $this->createUser();
        $base = [
            'sub' => $user->id,
            'token_type' => 'access',
            'iss' => 'green-tests',
            'aud' => 'green-test-clients',
        ];

        $claimSets = [
            array_replace($base, ['nbf' => time() + 300]),
            array_replace($base, ['iss' => 'wrong']),
            array_replace($base, ['aud' => 'wrong']),
            array_replace($base, ['sub' => '']),
            array_replace($base, ['sub' => 'not-an-id']),
            array_replace($base, ['sub' => 0]),
        ];

        foreach ($claimSets as $claims) {
            $token = app(JwtService::class)->encode($claims);

            try {
                app(JwtAuthenticator::class)->authenticate(new Request(server: [
                    'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
                ]));
                self::fail('Invalid JWT claims should fail.');
            } catch (AuthenticationException $e) {
                self::assertSame(401, $e->getStatusCode());
            }
        }
    }

    public function testConflictingAuthenticationMiddlewareCannotReplaceIdentity(): void
    {
        $user = $this->createUser();
        auth_service()->login($user);
        $request = new Request(server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . app(\App\Services\JwtTokenService::class)->issueToken($user),
        ]);
        (new SessionAuthMiddleware())->handle($request, fn (): Response => new Response('ok'));

        $this->expectException(AuthenticationException::class);
        (new TokenAuthMiddleware())->handle($request, fn (): Response => new Response('unreachable'));
    }

    public function testJwtRouteRejectsInvalidBearerEvenWithValidSession(): void
    {
        auth_service()->login($this->createUser());

        $response = $this->app->handle(new Request(server: [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/api/profile',
            'HTTP_AUTHORIZATION' => 'Bearer invalid',
            'HTTP_ACCEPT' => 'application/json',
        ]));

        self::assertSame(401, $response->getStatusCode());
        self::assertNull(auth()->user());
    }


}
