<?php

namespace App\Middleware;

use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Http\ValidationException;
use YasserElgammal\Green\Middleware\MiddlewareInterface;

class ValidationExceptionMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        try {
            return $next($request);
        } catch (ValidationException $e) {
            $accept = $request->header('Accept', '');
            $path = $request->getPath();
            $expectsJson = str_contains($accept, 'application/json') || str_starts_with($path, '/api');

            if ($expectsJson) {
                return api()->error('Validation failed.', $e->getErrors(), 422);
            }

            $errors = $e->getErrors();
            $firstError = reset($errors)[0] ?? 'The given data was invalid.';
            
            session()->flash('error', $firstError);
            session()->flash('errors', $errors);
            
            $referer = $_SERVER['HTTP_REFERER'] ?? '/';
            return redirect($referer);
        }
    }
}
