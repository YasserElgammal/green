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
    #[Route('POST', '/api/login', ['throttle:5,1,auth-login'])]
    public function login(LoginPayload $payload): JsonResponse
    {
        $credentials = $payload->validated();
        $user = (new UserTable())->fetchFirst('email', $credentials['email']);

        if (!$user || !password_verify($credentials['password'], $user->password)) {
            return api()->error('The email or password is incorrect.', [], 401);
        }

        $token = app(\App\Services\JwtTokenService::class)->issueToken($user);
        $refreshToken = config('auth.jwt.refresh_tokens', false)
            ? app(\App\Services\JwtTokenService::class)->issueRefreshToken($user)
            : null;

        return api()->success('Login successful.', [
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.ttl', 3600),
        ]);
    }

    #[Route('POST', '/api/refresh', ['throttle:10,1,auth-refresh'])]
    public function refresh(Request $request): JsonResponse
    {
        if (!config('auth.jwt.refresh_tokens', false)) {
            return api()->error('Refresh tokens are not enabled.', [], 404);
        }
        $refreshToken = $request->input('refresh_token');

        if (!is_string($refreshToken) || $refreshToken === '') {
            return api()->fieldError('refresh_token', 'Refresh token is required.', 422);
        }

        $rotated = app(\App\Services\JwtTokenService::class)->rotateRefreshToken($refreshToken);

        if ($rotated === null) {
            return api()->fieldError('refresh_token', 'Invalid refresh token.', 401);
        }

        $newAccessToken = app(\App\Services\JwtTokenService::class)->issueToken($rotated['user']);
        $newRefreshToken = $rotated['refresh_token'];

        return api()->success('Token refreshed successfully.', [
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.ttl', 3600),
        ]);
    }

    #[Route('POST', '/api/register', ['throttle:5,1,auth-register'])]
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
