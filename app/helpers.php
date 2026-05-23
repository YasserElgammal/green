<?php

use App\Services\AuthService;
use App\Support\ApiResponder;

if (!function_exists('auth')) {
    /**
     * Get the global AuthService instance.
     */
    function auth(): AuthService
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new AuthService();
        }
        return $instance;
    }
}

if (!function_exists('api')) {
    function api(): ApiResponder
    {
        static $responder = null;
        if ($responder === null) {
            $responder = new ApiResponder();
        }
        return $responder;
    }
}
