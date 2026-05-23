<?php

namespace App\Controllers\Api;

use App\Payloads\RegisterPayload;
use App\Tables\UserTable;
use App\Transformers\UserTransformer;
use YasserElgammal\Green\Http\JsonResponse;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Routing\Route;
use YasserElgammal\Green\Transformer\TransformerResponse;

class AuthController
{
    #[Route('POST', '/api/login')]
    public function login(Request $request): JsonResponse
    {
        $users = new UserTable();
        $user  = $users->fetchFirst('email', $request->input('email'));

        if (!$user || !password_verify((string) $request->input('password'), $user->password)) {
            return response_json(['error' => 'Unauthorized'], 401);
        }

        $token = auth()->issueToken($user);
        $refreshToken = auth()->issueRefreshToken($user);

        return response_json([
            'access_token'  => $token,
            'refresh_token' => $refreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => (int) ($_ENV['JWT_TTL'] ?? 3600),
        ]);
    }

    #[Route('POST', '/api/refresh')]
    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->input('refresh_token');

        if (!$refreshToken) {
            return response_json(['error' => 'Refresh token is required.'], 422);
        }

        $user = auth()->verifyRefreshToken($refreshToken);

        if (!$user) {
            return response_json(['error' => 'Invalid refresh token.'], 401);
        }

        // Issue new tokens (rotate refresh token for security)
        $newAccessToken  = auth()->issueToken($user);
        $newRefreshToken = auth()->issueRefreshToken($user);

        return response_json([
            'access_token'  => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'token_type'    => 'Bearer',
            'expires_in'    => (int) ($_ENV['JWT_TTL'] ?? 3600),
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

        //TODO send verification email

        return response_json([
            'message' => 'User registered successfully!',
            'user'    => [
                'name'  => $data['name'],
                'email' => $data['email'],
            ],
        ]);
    }
}
