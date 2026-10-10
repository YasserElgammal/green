<?php

namespace App\Services;

use App\Tables\UserTable;
use YasserElgammal\Green\Security\Jwt\JwtConfig;
use YasserElgammal\Green\Security\Jwt\JwtService;

class AuthService
{
    private ?object $user = null;
    private bool $resolved = false;
    private JwtService $tokens;

    public function __construct()
    {
        $this->tokens = new JwtService(new JwtConfig([
            'secret' => config('jwt.secret', ''),
            'ttl' => (int) config('jwt.ttl', 3600),
        ]));
    }

    public function user(): ?object
    {
        if ($this->resolved) {
            return $this->user;
        }

        $this->resolved = true;
        $userId = session()->get('user_id');
        $this->user = $userId ? (new UserTable())->fetchById($userId) : null;

        if ($userId && !$this->user) {
            session()->invalidate();
        }

        return $this->user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    public function login(object $user): void
    {
        session()->regenerateId(true);
        session()->put('user_id', $user->id);
        session()->put('user_name', $user->name);
        $this->user = $user;
        $this->resolved = true;
    }

    public function logout(): void
    {
        session()->invalidate();
        $this->user = null;
        $this->resolved = true;
    }

    public function issueToken(object $user): string
    {
        return $this->tokens->encode([
            'sub' => $user->id,
            'email' => $user->email,
        ]);
    }

    public function issueRefreshToken(object $user): string
    {
        $refreshToken = bin2hex(random_bytes(32));

        (new UserTable())->update($user->id, [
            'refresh_token' => $refreshToken,
        ]);

        return $refreshToken;
    }

    public function verifyRefreshToken(string $token): ?object
    {
        return (new UserTable())->fetchFirst('refresh_token', $token);
    }

    public function resolveFromJwt(string $token): ?object
    {
        $claims = $this->tokens->decode($token);

        if (!$claims || !isset($claims->sub)) {
            return null;
        }

        return (new UserTable())->fetchById($claims->sub);
    }
}
