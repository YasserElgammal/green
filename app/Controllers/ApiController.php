<?php

namespace App\Controllers;

use App\Payloads\RegisterPayload;
use App\Middleware\AuthMiddleware;
use App\Tables\PostTable;
use App\Tables\UserTable;
use App\Transformers\PostTransformer;
use App\Transformers\UserTransformer;
use YasserElgammal\Green\Http\JsonResponse;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Routing\Route;
use YasserElgammal\Green\Transformer\TransformerResponse;

class ApiController
{
    // ─── Users ───────────────────────────────────────────────────────────────

    /**
     * GET /api/users
     *
     * Supports pagination via query string:
     *   ?page=1&per_page=10
     */
    #[Route('GET', '/api/users')]
    public function index(Request $request): JsonResponse
    {
        $users = new UserTable();
        $users->include(['posts.comments', 'roles']);

        // Check if pagination is requested
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

    /**
     * GET /api/users/{id}
     */
    #[Route('GET', '/api/users/{id}')]
    public function show(int $id, Request $request): JsonResponse
    {
        $users = new UserTable();
        $users->include(['posts.comments', 'roles']);

        $user = $users->fetchByIdOrFail($id);

        return TransformerResponse::item($user, new UserTransformer());
    }

    /**
     * POST /api/users
     */
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

    /**
     * PUT /api/users/{id}
     */
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

    /**
     * POST /api/login
     */
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

    /**
     * DELETE /api/users/{id}
     */
    #[Route('DELETE', '/api/users/{id}', [AuthMiddleware::class])]
    public function destroy(int $id): JsonResponse
    {
        $users = new UserTable();
        $users->deleteById($id);

        return response_json(['message' => "User [{$id}] deleted."]);
    }

    // ─── Posts ────────────────────────────────────────────────────────────────

    /**
     * GET /api/posts
     *
     * Supports pagination via query string:
     *   ?page=1&per_page=10
     */
    #[Route('GET', '/api/posts')]
    public function posts(Request $request): JsonResponse
    {
        $posts = new PostTable();
        $posts->include(['author', 'comments.likes']);

        $page    = (int) ($request->input('page') ?: 1);
        $perPage = (int) ($request->input('per_page') ?: 15);

        return TransformerResponse::paginated(
            $posts->paginate($perPage, $page),
            new PostTransformer()
        );
    }

    /**
     * GET /api/posts/{id}
     */
    #[Route('GET', '/api/posts/{id}')]
    public function post(int $id, Request $request): JsonResponse
    {
        $posts = new PostTable();
        $posts->include(['author', 'comments.likes']);

        return TransformerResponse::item(
            $posts->fetchByIdOrFail($id),
            new PostTransformer()
        );
    }
    /**
     * POST /api/register
     */
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
