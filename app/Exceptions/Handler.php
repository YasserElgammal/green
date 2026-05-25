<?php

namespace App\Exceptions;

use App\Exceptions\Contracts\ErrorResponderInterface;
use Throwable;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;

class Handler
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
        if ($this->isApiRequest($request)) {
            return $this->apiHandler->handle($e, $request);
        }

        return $this->webHandler->handle($e, $request);
    }

    public function handleResponse(Response $response, Request $request): Response
    {
        if ($response->getStatusCode() < 400) {
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
