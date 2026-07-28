<?php

namespace App\Exceptions;

use App\Exceptions\Contracts\ErrorResponderInterface;
use Throwable;
use YasserElgammal\Green\Exceptions\ExceptionHandler;
use YasserElgammal\Green\Http\JsonResponse;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\ValidationException;

class ApiExceptionHandler extends ExceptionHandler implements ErrorResponderInterface
{
    public function __construct(
        private ?ErrorStatusResolver $statusResolver = null,
        private ?bool $debug = null,
    ) {
        parent::__construct();
        $this->statusResolver ??= new ErrorStatusResolver();
    }

    protected function isDebug(): bool
    {
        return $this->debug ?? parent::isDebug();
    }

    public function handle(Throwable $e, Request $request): JsonResponse
    {
        $traceId = uniqid('ERR_', true);

        return $this->renderJson($e, $this->statusResolver->resolve($e), $this->isDebug(), $traceId);
    }

    public function handleStatus(int $statusCode): JsonResponse
    {
        $title = $this->getErrorTitle($statusCode);

        return api()->error($title, [
            'exception' => [$title],
        ], $statusCode);
    }

    protected function renderJson(Throwable $e, int $statusCode, bool $isDebug, string $traceId): JsonResponse
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
                'trace_id' => $traceId,
            ];
        }

        return api()->error($message, $errors, $statusCode);
    }
}
