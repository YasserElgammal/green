<?php

namespace App\Controllers\Api;

use App\Middleware\AuthMiddleware;
use App\Tables\UserTable;
use App\Transformers\UserTransformer;
use YasserElgammal\Green\Http\JsonResponse;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Routing\Route;
use YasserElgammal\Green\Transformer\TransformerResponse;

class UserController
{
    #[Route('GET', '/api/users')]
    public function index(Request $request): JsonResponse
    {
        $users = new UserTable();
        $users->include(['posts.comments', 'roles']);

        $page    = (int) ($request->input('page') ?: 1);
        $perPage = (int) ($request->input('per_page') ?: 15);

        if ($request->input('page') !== null) {
            return TransformerResponse::paginated(
                $users->paginate($perPage, $page),
                new UserTransformer()
            );
        }

        return TransformerResponse::collection(
            $users->fetchAll(),
            new UserTransformer()
        );
    }

    #[Route('GET', '/api/users/{id}')]
    public function show(int $id): JsonResponse
    {
        $users = new UserTable();
        $users->include(['posts.comments', 'roles']);

        return TransformerResponse::item(
            $users->fetchByIdOrFail($id),
            new UserTransformer()
        );
    }

    #[Route('POST', '/api/users', [AuthMiddleware::class])]
    public function store(Request $request): JsonResponse
    {
        $users = new UserTable();

        $user = $users->insert([
            'name'     => $request->input('name'),
            'email'    => $request->input('email'),
            'password' => password_hash($request->input('password') ?: 'secret', PASSWORD_DEFAULT),
        ]);

        return TransformerResponse::item($user, new UserTransformer(), 201);
    }

    #[Route('PUT', '/api/users/{id}', [AuthMiddleware::class])]
    public function update(int $id, Request $request): JsonResponse
    {
        $users = new UserTable();

        $data = [
            'name'  => $request->input('name'),
            'email' => $request->input('email'),
        ];

        if ($request->input('password')) {
            $data['password'] = password_hash($request->input('password'), PASSWORD_DEFAULT);
        }

        $users->update($id, array_filter($data));

        return TransformerResponse::item(
            $users->fetchByIdOrFail($id),
            new UserTransformer()
        );
    }

    #[Route('DELETE', '/api/users/{id}', [AuthMiddleware::class])]
    public function destroy(int $id): JsonResponse
    {
        $users = new UserTable();
        $users->deleteById($id);

        return response_json(['message' => "User [{$id}] deleted."]);
    }
}
