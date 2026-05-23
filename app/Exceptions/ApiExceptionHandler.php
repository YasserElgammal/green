<?php

namespace App\Exceptions;

use Throwable;
use YasserElgammal\Green\Exceptions\ExceptionHandler;
use YasserElgammal\Green\Http\JsonResponse;
use YasserElgammal\Green\Http\ValidationException;

class ApiExceptionHandler extends ExceptionHandler
{
    protected function renderJson(Throwable $e, int $statusCode, bool $isDebug): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return api()->error('Validation failed.', $e->getErrors(), 422);
        }

        $message = $isDebug
            ? $e->getMessage()
            : ($statusCode >= 500 ? $this->cleanMessage($e->getMessage()) : $this->getErrorTitle($statusCode));

        $errors = [
            'exception' => [$this->getErrorTitle($statusCode)],
        ];

        if ($isDebug) {
            $errors['debug'] = [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace_id' => uniqid('ERR_'),
            ];
        }

        return api()->error($message, $errors, $statusCode);
    }
}
