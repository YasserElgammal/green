<?php

namespace App\Controllers\Api;

use App\Middleware\TokenAuthMiddleware;
use App\Payloads\ChangePasswordPayload;
use App\Payloads\UpdateProfilePayload;
use App\Payloads\DeleteAccountPayload;
use App\Services\ProfileService;
use App\Tables\UserTable;
use App\Transformers\UserTransformer;
use YasserElgammal\Green\Http\JsonResponse;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Routing\Route;
use YasserElgammal\Green\Transformer\TransformerResponse;

class ProfileController
{
    private ProfileService $profileService;

    public function __construct()
    {
        $this->profileService = new ProfileService();
    }

    #[Route('GET', '/api/profile', [TokenAuthMiddleware::class])]
    public function show(Request $request): JsonResponse
    {
        return TransformerResponse::item(
            $request->getAttribute('user'),
            new UserTransformer()
        );
    }

    #[Route('PUT', '/api/profile', [TokenAuthMiddleware::class])]
    public function update(UpdateProfilePayload $payload): JsonResponse
    {
        $user = $payload->getAttribute('user');
        $data = $payload->validated();

        $usersTable = new UserTable();
        $existing = $usersTable->fetchFirst('email', $data['email']);
        if ($existing && $existing->id !== $user->id) {
            return response_json(['error' => 'Email already in use.'], 422);
        }

        // Standard PUT request typically doesn't contain files, but check files just in case
        $avatarFile = $_FILES['avatar'] ?? null;

        $this->profileService->updateProfile($user->id, $data, $avatarFile);

        $updatedUser = $usersTable->fetchById($user->id);

        return TransformerResponse::item($updatedUser, new UserTransformer());
    }

    #[Route('POST', '/api/profile', [TokenAuthMiddleware::class])]
    public function updateWithPost(UpdateProfilePayload $payload): JsonResponse
    {
        // For API clients uploading avatars via multipart/form-data POST
        $user = $payload->getAttribute('user');
        $data = $payload->validated();

        $usersTable = new UserTable();
        $existing = $usersTable->fetchFirst('email', $data['email']);
        if ($existing && $existing->id !== $user->id) {
            return response_json(['error' => 'Email already in use.'], 422);
        }

        $avatarFile = $_FILES['avatar'] ?? null;

        $this->profileService->updateProfile($user->id, $data, $avatarFile);

        $updatedUser = $usersTable->fetchById($user->id);

        return TransformerResponse::item($updatedUser, new UserTransformer());
    }

    #[Route('PUT', '/api/profile/password', [TokenAuthMiddleware::class])]
    public function changePassword(ChangePasswordPayload $payload): JsonResponse
    {
        $user = $payload->getAttribute('user');
        $data = $payload->validated();

        $success = $this->profileService->changePassword(
            $user->id,
            $data['current_password'],
            $data['password']
        );

        if (!$success) {
            return response_json(['error' => 'The provided current password does not match our records.'], 422);
        }

        return response_json(['message' => 'Password changed successfully.']);
    }

    #[Route('DELETE', '/api/profile', [TokenAuthMiddleware::class])]
    public function destroy(DeleteAccountPayload $payload): JsonResponse
    {
        $user = $payload->getAttribute('user');
        $data = $payload->validated();

        $success = $this->profileService->deleteAccount($user->id, $data['password']);

        if (!$success) {
            return response_json(['error' => 'The provided current password does not match our records.'], 422);
        }

        // Clear web session if any exists
        session()->flush();

        return response_json(['message' => 'Account deleted successfully.']);
    }
}
