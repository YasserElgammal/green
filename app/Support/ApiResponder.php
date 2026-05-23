<?php

namespace App\Support;

use YasserElgammal\Green\Database\Model;
use YasserElgammal\Green\Http\JsonResponse;
use YasserElgammal\Green\Transformer\Transformer;

final class ApiResponder
{
    public function success(
        string $message = 'Operation completed successfully.',
        array $data = [],
        int $status = 200
    ): JsonResponse {
        return response_json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public function error(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        return response_json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    public function fieldError(string $field, string $message, int $status = 422): JsonResponse
    {
        return $this->error($message, [
            $field => [$message],
        ], $status);
    }

    public function item(
        Model $model,
        Transformer $transformer,
        string $message = 'Operation completed successfully.',
        int $status = 200
    ): JsonResponse {
        return $this->success($message, [
            'item' => $transformer->process($model),
        ], $status);
    }

    public function collection(
        array $models,
        Transformer $transformer,
        string $message = 'Operation completed successfully.',
        int $status = 200
    ): JsonResponse {
        return $this->success($message, [
            'items' => $transformer->collection($models),
        ], $status);
    }

    public function paginated(
        array $paginatorResult,
        Transformer $transformer,
        string $message = 'Operation completed successfully.',
        int $status = 200
    ): JsonResponse {
        $models = $paginatorResult['data'] ?? [];
        $meta = $paginatorResult['meta'] ?? [];

        return $this->success($message, [
            'items' => $transformer->collection($models),
            'meta' => $meta,
        ], $status);
    }
}
