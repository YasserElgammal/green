<?php

namespace App\Exceptions;

use App\Exceptions\Contracts\ErrorResponderInterface;
use Throwable;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Exceptions\ExceptionHandler;

class Handler extends ExceptionHandler
{
    public function __construct(
        private ?ErrorResponderInterface $apiHandler = null,
        private ?ErrorResponderInterface $webHandler = null,
    ) {
        $this->apiHandler ??= new ApiExceptionHandler();
        $this->webHandler ??= new WebExceptionHandler();
    }

    public function handle(Throwable $e, Request $request): Response
    {
        $response = $this->isApiRequest($request)
            ? $this->apiHandler->handle($e, $request)
            : $this->webHandler->handle($e, $request);

        return $response->setHeader('X-Green-Exception-Handled', '1');
    }

    public function handleResponse(Response $response, Request $request): Response
    {
        if ($response->getStatusCode() < 400 || $response->hasHeader('X-Green-Exception-Handled')) {
            return $response;
        }

        if ($this->isApiRequest($request)) {
            return $this->apiHandler->handleStatus($response->getStatusCode());
        }

        return $this->webHandler->handleStatus($response->getStatusCode());
    }

    private function isApiRequest(Request $request): bool
    {
        if (str_starts_with($request->getPath(), '/api')) {
            return true;
        }

        $accept = $request->header('Accept', '');

        return str_contains($accept, 'application/json');
    }
}