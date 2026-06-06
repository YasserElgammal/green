<?php

namespace App\Services;

use App\Tables\UserTable;
use YasserElgammal\Green\Security\Jwt\JwtConfig;
use YasserElgammal\Green\Security\Jwt\JwtService;

class AuthService
{
    private ?object $user = null;
    private bool $resolved = false;
    private mixed $resolvedUserId = null;
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
        $userId = session()->get('user_id');

        if ($this->resolved && $this->resolvedUserId === $userId) {
            return $this->user;
        }

        if ($userId) {
            try {
                $usersTable = new UserTable();
                $this->user = $usersTable->fetchById($userId);
            } catch (\Throwable) {
                $this->user = null;
            }

            if (!$this->user) {
                session()->flush();
                $userId = null;
            } else {
                $this->syncSessionUser($this->user);
            }
        } else {
            $this->user = null;
        }

        $this->resolved = true;
        $this->resolvedUserId = $userId;

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
        $this->syncSessionUser($user);
        $this->user = $user;
        $this->resolved = true;
        $this->resolvedUserId = $user->id;
    }

    /**
     * Web: Log a user out.
     */
    public function logout(): void
    {
        session()->flush();
        $this->user = null;
        $this->resolved = true;
        $this->resolvedUserId = null;
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

    private function syncSessionUser(object $user): void
    {
        session()->put('user_id', $user->id);
        session()->put('user_name', $user->name);
        session()->put('user_avatar', $user->avatar ?? null);
        session()->put('user_is_admin', (int) ($user->is_admin ?? 0));
    }
}
