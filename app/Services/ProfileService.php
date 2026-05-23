<?php

namespace App\Services;

use App\Tables\UserTable;
use YasserElgammal\Green\Security\PasswordHasher;

class ProfileService
{
    private UserTable $userTable;
    private PasswordHasher $hasher;

    public function __construct()
    {
        $this->userTable = new UserTable();
        $this->hasher = new PasswordHasher();
    }

    /**
     * Update user profile information.
     */
    public function updateProfile(int $userId, array $data, ?array $avatarFile = null): ?string
    {
        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        $avatarPath = null;
        // Process avatar upload
        if ($avatarFile && $avatarFile['error'] === UPLOAD_ERR_OK) {
            $fileContent = file_get_contents($avatarFile['tmp_name']);
            $filename = 'uploads/avatars/' . uniqid() . '_' . basename($avatarFile['name']);
            drive()->put($filename, $fileContent);
            $updateData['avatar'] = $filename;
            $avatarPath = $filename;
        }

        $this->userTable->update($userId, $updateData);

        return $avatarPath;
    }

    /**
     * Change user password.
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->userTable->fetchByIdOrFail($userId);

        if (!$this->hasher->verify($currentPassword, $user->password)) {
            return false;
        }

        $this->userTable->update($userId, [
            'password' => $this->hasher->hash($newPassword)
        ]);

        return true;
    }

    /**
     * Delete user account.
     */
    public function deleteAccount(int $userId, string $password): bool
    {
        $user = $this->userTable->fetchByIdOrFail($userId);

        if (!$this->hasher->verify($password, $user->password)) {
            return false;
        }

        $this->userTable->deleteById($userId);

        return true;
    }
}
