<?php

declare(strict_types=1);

namespace Tests\Services;

use App\Auth\SessionIdentityKey;
use App\Auth\SessionAuthenticator;
use App\Middleware\SessionAuthMiddleware;
use Tests\TestCase;
use YasserElgammal\Green\Application;
use YasserElgammal\Green\Auth\AuthenticationException;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;

final class AuthenticatorTest extends TestCase
{
    public function testSessionIdentityKeysAreDerivedFromModelNames(): void
    {
        self::assertSame('auth.user.user_id', SessionIdentityKey::forModel(\App\Models\User::class));
        self::assertSame(
            'auth.customer_account.customer_account_id',
            SessionIdentityKey::forModel(CustomerAccount::class),
        );
    }

    public function testSessionIdentityUsesOneSharedRequestObject(): void
    {
        $user = $this->createUser();
        auth_service()->login($user);
        $request = new Request();
        $resolved = auth()->resolve($request, app(SessionAuthenticator::class));

        self::assertSame($resolved, auth()->user());
        self::assertSame($resolved, $request->user());
        self::assertSame((int) $user->id, (int) auth()->id());
    }

    public function testMissingAndDeletedSessionIdentitiesAreAnonymous(): void
    {
        self::assertNull((app(SessionAuthenticator::class))->authenticate(new Request()));
        session()->put(SessionIdentityKey::forModel(\App\Models\User::class), 999);
        self::assertNull((app(SessionAuthenticator::class))->authenticate(new Request()));
        self::assertNull(session()->get(SessionIdentityKey::forModel(\App\Models\User::class)));
    }

    public function testLoginAndLogoutSynchronizeTheCurrentRequestIdentity(): void
    {
        $request = new Request();
        auth()->resolve($request, app(SessionAuthenticator::class));
        self::assertTrue(auth()->guest());

        $user = $this->createUser();
        auth_service()->login($user);
        self::assertSame($user, auth()->user());
        self::assertSame($user, $request->user());

        auth_service()->logout();
        self::assertNull(auth()->user());
        self::assertNull($request->user());
        self::assertTrue(auth()->guest());
    }

    public function testSessionRouteIgnoresBearerCredentials(): void
    {
        $user = $this->createUser();
        auth_service()->login($user);
        $request = new Request(server: [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/profile',
            'HTTP_AUTHORIZATION' => 'Bearer invalid',
        ]);

        $response = $this->app->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($user->id, $request->user()?->id);
    }

    public function testProductionSessionCookieDefaultsAreSecure(): void
    {
        $previousEnvironment = $_ENV['APP_ENV'] ?? null;
        $previousOverride = $_ENV['SESSION_SECURE_COOKIE'] ?? null;
        $_ENV['APP_ENV'] = 'production';
        unset($_ENV['SESSION_SECURE_COOKIE']);

        try {
            $settings = require dirname(__DIR__, 2) . '/config/session.php';
            self::assertTrue($settings['cookie_secure']);
            self::assertTrue($settings['cookie_httponly']);
            self::assertSame('Lax', $settings['cookie_samesite']);
        } finally {
            if ($previousEnvironment === null) {
                unset($_ENV['APP_ENV']);
            } else {
                $_ENV['APP_ENV'] = $previousEnvironment;
            }

            if ($previousOverride === null) {
                unset($_ENV['SESSION_SECURE_COOKIE']);
            } else {
                $_ENV['SESSION_SECURE_COOKIE'] = $previousOverride;
            }
        }
    }
}

final class CustomerAccount
{
}
