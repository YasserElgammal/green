<?php

namespace App\Controllers\Api;

use App\Middleware\TokenAuthMiddleware;
use App\Tables\UserTable;
use App\Transformers\UserTransformer;
use YasserElgammal\Green\Http\JsonResponse;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Routing\Route;

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
            return api()->paginated(
                $users->paginate($perPage, $page),
                new UserTransformer()
            );
        }

        return api()->collection(
            $users->fetchAll(),
            new UserTransformer()
        );
    }

    #[Route('GET', '/api/users/{id}')]
    public function show(int $id): JsonResponse
    {
        $users = new UserTable();
        $users->include(['posts.comments', 'roles']);

        return api()->item(
            $users->fetchByIdOrFail($id),
            new UserTransformer()
        );
    }

    #[Route('POST', '/api/users', [TokenAuthMiddleware::class])]
    public function store(Request $request): JsonResponse
    {
        $users = new UserTable();

        $user = $users->insert([
            'name'     => $request->input('name'),
            'email'    => $request->input('email'),
            'password' => password_hash($request->input('password') ?: 'secret', PASSWORD_DEFAULT),
        ]);

        return api()->item($user, new UserTransformer(), 'User created successfully.', 201);
    }

    #[Route('PUT', '/api/users/{id}', [TokenAuthMiddleware::class])]
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

        return api()->item(
            $users->fetchByIdOrFail($id),
            new UserTransformer(),
            'User updated successfully.'
        );
    }

    #[Route('DELETE', '/api/users/{id}', [TokenAuthMiddleware::class])]
    public function destroy(int $id): JsonResponse
    {
        $users = new UserTable();
        $users->deleteById($id);

        return api()->success("User [{$id}] deleted.");
    }
}
