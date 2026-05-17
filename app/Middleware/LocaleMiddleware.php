<?php

namespace App\Middleware;

use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\Response;
use YasserElgammal\Green\Middleware\MiddlewareInterface;
use YasserElgammal\Green\Translation\TranslatorManager;

class LocaleMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Get the requested locale from the session or fallback to default
        $locale = session()->get('locale');

        if ($locale) {
            // Apply it to the translator manager
            TranslatorManager::getInstance()->setLocale($locale);
        }

        return $next($request);
    }
}
