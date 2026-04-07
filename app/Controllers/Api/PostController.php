<?php

namespace App\Controllers\Api;

use App\Tables\PostTable;
use App\Transformers\PostTransformer;
use YasserElgammal\Green\Http\JsonResponse;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Routing\Route;
use YasserElgammal\Green\Transformer\TransformerResponse;

class PostController
{
    #[Route('GET', '/api/posts')]
    public function index(Request $request): JsonResponse
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

    #[Route('GET', '/api/posts/{id}')]
    public function show(int $id): JsonResponse
    {
        $posts = new PostTable();
        $posts->include(['author', 'comments.likes']);

        return TransformerResponse::item(
            $posts->fetchByIdOrFail($id),
            new PostTransformer()
        );
    }
}
