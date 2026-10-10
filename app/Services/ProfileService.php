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

        $this->users->update($userId, ['password' => $this->passwords->hash($newPassword)]);
        return true;
    }

    public function deleteAccount(int $userId, string $password): bool
    {
        $user = $this->users->fetchByIdOrFail($userId);

        if (!$this->passwords->verify($password, $user->password)) {
            return false;
        }

        $this->users->deleteById($userId);
        return true;
    }
}
