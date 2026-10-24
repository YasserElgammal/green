<?php

use App\Services\AuthService;
use App\Support\ApiResponder;

if (!function_exists('auth')) {
    function auth(): \YasserElgammal\Green\Auth\Auth
    {
        return app(\YasserElgammal\Green\Auth\Auth::class);
    }
}

if (!function_exists('auth_service')) {
    function auth_service(): AuthService
    {
        return app(AuthService::class);
    }
}

if (!function_exists('api')) {
    function api(): ApiResponder
    {
        static $instance = null;
        return $instance ??= new ApiResponder();
    }
}
