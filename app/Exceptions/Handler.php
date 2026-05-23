<?php

namespace App\Exceptions;

use Throwable;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;

class Handler
{
    public function handle(Throwable $e, Request $request): Response
    {
        if ($this->isApiRequest($request)) {
            return (new ApiExceptionHandler())->handle($e, $request);
        }

        return (new WebExceptionHandler())->handle($e, $request);
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
