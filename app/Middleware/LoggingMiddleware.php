<?php

namespace App\Middleware;

use YasserElgammal\Green\Middleware\MiddlewareInterface;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;

class LoggingMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $time = microtime(true);
        /** @var Response $response */
        $response = $next($request);
        $duration = microtime(true) - $time;
        
        error_log(sprintf("[%s] %s - %fms", $request->getMethod(), $request->getPath(), $duration * 1000));
        
        return $response;
    }
}
