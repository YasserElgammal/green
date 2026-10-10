<?php

namespace App\Controllers\Api;

use App\Payloads\LoginPayload;
use App\Payloads\RegisterPayload;
use App\Tables\UserTable;
use YasserElgammal\Green\Http\JsonResponse;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Routing\Route;

class AuthController
{
    #[Route('POST', '/api/login')]
    public function login(LoginPayload $payload): JsonResponse
    {
        $credentials = $payload->validated();
        $user = (new UserTable())->fetchFirst('email', $credentials['email']);

        if (!$user || !password_verify($credentials['password'], $user->password)) {
            return api()->error('The email or password is incorrect.', [], 401);
        }

        $token = auth()->issueToken($user);
        $refreshToken = auth()->issueRefreshToken($user);

        return api()->success('Login successful.', [
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.ttl', 3600),
        ]);
    }

    #[Route('POST', '/api/refresh')]
    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->input('refresh_token');

        if (!$refreshToken) {
            return api()->fieldError('refresh_token', 'Refresh token is required.', 422);
        }

        $user = auth()->verifyRefreshToken($refreshToken);

        if (!$user) {
            return api()->fieldError('refresh_token', 'Invalid refresh token.', 401);
        }

        $newAccessToken = auth()->issueToken($user);
        $newRefreshToken = auth()->issueRefreshToken($user);

        return api()->success('Token refreshed successfully.', [
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.ttl', 3600),
        ]);
    }

    #[Route('POST', '/api/register')]
    public function register(RegisterPayload $payload): JsonResponse
    {
        $data = $payload->validated();

        $users = new UserTable();
        $users->insert([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
        ]);

        // TODO: Send verification email.

        return api()->success('User registered successfully!', [
            'user' => [
                'name' => $data['name'],
                'email' => $data['email'],
            ],
        ], 201);
    }

}
