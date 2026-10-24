<?php

namespace Tests\Jwt\Services;

use Tests\JwtTestCase;
use App\Services\{JwtTokenService, ProfileService};
use YasserElgammal\Green\AuthJwt\Refresh\RefreshTokenManager;

final class RefreshLifecycleTest extends JwtTestCase
{
    public function testPasswordChangeRevokesAllDevicesInPackageStorage(): void
    {
        $user = $this->createUser();
        $tokens = app(JwtTokenService::class);
        $first = $tokens->issueRefreshToken($user, 'Laptop');
        $second = $tokens->issueRefreshToken($user, 'Mobile');
        self::assertNotNull($tokens->verifyRefreshToken($first));
        self::assertNotNull($tokens->verifyRefreshToken($second));
        self::assertTrue((new ProfileService())->changePassword($user->id, 'password', 'new-password'));
        self::assertNull($tokens->verifyRefreshToken($first));
        self::assertNull($tokens->verifyRefreshToken($second));
    }

    public function testDeletingAccountRevokesItsTokens(): void
    {
        $user = $this->createUser();
        $token = app(JwtTokenService::class)->issueRefreshToken($user);
        self::assertTrue((new ProfileService())->deleteAccount($user->id, 'password'));
        self::assertNull(app(RefreshTokenManager::class)->verify($token));
        self::assertNotNull($this->connection->fetchOne('SELECT revoked_at FROM refresh_tokens'));
    }
}
