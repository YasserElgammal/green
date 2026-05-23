<?php

use App\Services\AuthService;

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
