<?php

namespace App\Services;

use App\Tables\UserTable;
use YasserElgammal\Green\Security\Jwt\JwtConfig;
use YasserElgammal\Green\Security\Jwt\JwtService;

class AuthService
{
    private ?object $user = null;
    private bool $resolved = false;
    private JwtService $jwtService;
    private JwtConfig $jwtConfig;

    public function __construct()
    {
        $this->jwtConfig = new JwtConfig([
            'secret' => $_ENV['JWT_SECRET'] ?? '',
            'ttl'    => (int) ($_ENV['JWT_TTL'] ?? 3600),
        ]);
        $this->jwtService = new JwtService($this->jwtConfig);
    }

    /**
     * Web: Resolve the user from the session.
     */
    public function user(): ?object
    {
        if ($this->resolved) {
            return $this->user;
        }

        $userId = session()->get('user_id');
        if ($userId) {
            $usersTable = new UserTable();
            $this->user = $usersTable->fetchById($userId);
        }

        $this->resolved = true;
        return $this->user;
    }

    /**
     * Check if a user is authenticated (web).
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Check if the user is a guest.
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Web: Log a user in.
     */
    public function login(object $user): void
    {
        session()->put('user_id', $user->id);
        session()->put('user_name', $user->name);
        session()->put('user_avatar', $user->avatar ?? null);
        $this->user = $user;
        $this->resolved = true;
    }

    /**
     * Web: Log a user out.
     */
    public function logout(): void
    {
        session()->flush();
        $this->user = null;
        $this->resolved = true;
    }

    /**
     * API: Issue a new JWT token for a user.
     */
    public function issueToken(object $user): string
    {
        return $this->jwtService->encode([
            'sub'   => $user->id,
            'email' => $user->email,
        ]);
    }

    /**
     * API: Issue a new refresh token for a user.
     */
    public function issueRefreshToken(object $user): string
    {
        $refreshToken = bin2hex(random_bytes(32));
        
        $usersTable = new UserTable();
        $usersTable->update($user->id, [
            'refresh_token' => $refreshToken
        ]);

        return $refreshToken;
    }

    /**
     * API: Resolve a user from a given JWT.
     */
    public function resolveFromJwt(string $token): ?object
    {
        $claims = $this->jwtService->decode($token);

        if (!$claims || !isset($claims->sub)) {
            return null;
        }

        $usersTable = new UserTable();
        $this->user = $usersTable->fetchById($claims->sub);
        $this->resolved = true;

        return $this->user;
    }

    /**
     * API: Verify refresh token and return user if valid.
     */
    public function verifyRefreshToken(string $token): ?object
    {
        $usersTable = new UserTable();
        return $usersTable->fetchFirst('refresh_token', $token);
    }
}
