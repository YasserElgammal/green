<?php

namespace App\Middleware;

use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Middleware\MiddlewareInterface;

class TrimStringsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $request->query = $this->trimArray($request->query);
        $request->post = $this->trimArray($request->post);

        return $next($request);
    }

    private function trimArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->trimArray($value);
                continue;
            }

            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }

        return $data;
    }
}
