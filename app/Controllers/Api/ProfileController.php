<?php

namespace App\Controllers\Api;

use App\Middleware\TokenAuthMiddleware;
use App\Payloads\ChangePasswordPayload;
use App\Payloads\DeleteAccountPayload;
use App\Payloads\UpdateProfilePayload;
use App\Services\ProfileService;
use App\Tables\UserTable;
use App\Transformers\UserTransformer;
use YasserElgammal\Green\Http\JsonResponse;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Routing\Route;

class ProfileController
{
    public function __construct(private readonly ProfileService $profiles = new ProfileService())
    {
    }

    #[Route('GET', '/api/profile', [TokenAuthMiddleware::class])]
    public function show(Request $request): JsonResponse
    {
        return api()->item($request->getAttribute('user'), new UserTransformer());
    }

    #[Route('PUT', '/api/profile', [TokenAuthMiddleware::class])]
    public function update(UpdateProfilePayload $payload): JsonResponse
    {
        $user = $payload->getAttribute('user');
        $data = $payload->validated();
        $users = new UserTable();
        $existing = $users->fetchFirst('email', $data['email']);

        if ($existing && (int) $existing->id !== (int) $user->id) {
            return api()->fieldError('email', 'That email address is already in use.');
        }

        $users->update($user->id, $data);

        return api()->item(
            $users->fetchByIdOrFail($user->id),
            new UserTransformer(),
            'Profile updated.',
        );
    }

    #[Route('PUT', '/api/profile/password', [TokenAuthMiddleware::class])]
    public function changePassword(ChangePasswordPayload $payload): JsonResponse
    {
        $user = $payload->getAttribute('user');
        $data = $payload->validated();

        if (!$this->profiles->changePassword($user->id, $data['current_password'], $data['password'])) {
            return api()->fieldError('current_password', 'The current password is incorrect.');
        }

        return api()->success('Password changed successfully.');
    }

    #[Route('DELETE', '/api/profile', [TokenAuthMiddleware::class])]
    public function delete(DeleteAccountPayload $payload): JsonResponse
    {
        $user = $payload->getAttribute('user');

        if (!$this->profiles->deleteAccount($user->id, $payload->validated()['password'])) {
            return api()->fieldError('password', 'The current password is incorrect.');
        }

        return api()->success('Account deleted successfully.');
    }
}
