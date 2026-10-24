<?php

namespace App\Services;

use App\Tables\UserTable;
use YasserElgammal\Green\Security\PasswordHasher;

class ProfileService
{
    public function __construct(
        private readonly UserTable $users = new UserTable(),
        private readonly PasswordHasher $passwords = new PasswordHasher(),
    ) {
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->users->fetchByIdOrFail($userId);

        if (!$this->passwords->verify($currentPassword, $user->password)) {
            return false;
        }

        \YasserElgammal\Green\Database\Database::getConnection()->transactional(function () use ($userId, $newPassword): void {
            $this->users->update($userId, ['password' => $this->passwords->hash($newPassword)]);
            $this->revokeRefreshTokens($userId);
        });
        return true;
    }

    public function deleteAccount(int $userId, string $password): bool
    {
        $user = $this->users->fetchByIdOrFail($userId);

        if (!$this->passwords->verify($password, $user->password)) {
            return false;
        }

        \YasserElgammal\Green\Database\Database::getConnection()->transactional(function () use ($userId): void {
            $this->revokeRefreshTokens($userId);
            $this->users->deleteById($userId);
        });
        return true;
    }

    private function revokeRefreshTokens(int $userId): void
    {
        if (config('auth.jwt.enabled', false) && config('auth.jwt.refresh_tokens', false)) {
            app(\YasserElgammal\Green\AuthJwt\Refresh\RefreshTokenManager::class)->revokeForOwner($userId);
        }
    }
}
