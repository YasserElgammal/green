<?php

namespace App\Exceptions;

use Throwable;
use YasserElgammal\Green\Http\ValidationException;

final class ErrorStatusResolver
{
    public function resolve(Throwable $e): int
    {
        if ($e instanceof ValidationException) {
            return 422;
        }

        if (method_exists($e, 'getStatusCode')) {
            $statusCode = (int) $e->getStatusCode();

            if ($this->isHttpErrorStatus($statusCode)) {
                return $statusCode;
            }
        }

        $code = (int) $e->getCode();

        return $this->isHttpErrorStatus($code) ? $code : 500;
    }

    private function isHttpErrorStatus(int $statusCode): bool
    {
        return $statusCode >= 400 && $statusCode < 600;
    }
}
