<?php

declare(strict_types=1);

namespace App\Services;

use YasserElgammal\Green\AuthJwt\JwtService;
use YasserElgammal\Green\AuthJwt\Refresh\RefreshTokenManager;

/** Application-facing workflows; persistence is owned by green-auth-jwt. */
final class JwtTokenService
{
    public function __construct(private readonly JwtService $tokens)
    {
    }

    public function issueToken(object $user): string
    {
        return $this->tokens->issueAccessToken($user->id, ['email' => $user->email]);
    }

    public function issueRefreshToken(object $user, ?string $deviceName = null): string
    {
        return $this->refresh()->issue($user->id, $deviceName);
    }

    public function verifyRefreshToken(string $token): ?object
    {
        return $this->refresh()->verify($token);
    }

    /** @return array{user: object, refresh_token: string}|null */
    public function rotateRefreshToken(string $token): ?array
    {
        $rotated = $this->refresh()->rotate($token);
        return $rotated === null ? null : ['user' => $rotated['identity'], 'refresh_token' => $rotated['token']];
    }

    private function refresh(): RefreshTokenManager
    {
        if (!config('auth.jwt.refresh_tokens', false)) {
            throw new \LogicException('Enable refresh storage after publishing and running the package migration.');
        }
        return app(RefreshTokenManager::class);
    }
}
