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

        return TransformerResponse::item($user, new UserTransformer());
    }

    #[Route('POST', '/api/register')]
    public function register(RegisterPayload $payload): JsonResponse
    {
        $data = $payload->validated();

        return response_json([
            'message' => 'User registered successfully!',
            'user'    => [
                'name'  => $data['name'],
                'email' => $data['email'],
            ],
        ]);
    }
}
