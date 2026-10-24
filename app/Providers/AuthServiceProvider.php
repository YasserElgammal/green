<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\SessionAuthenticator;
use App\Services\AuthService;
use YasserElgammal\Green\Support\ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SessionAuthenticator::class);
        $this->app->singleton(AuthService::class);
        $this->app->singleton(\App\Console\Commands\InstallJwtRefreshCommand::class,
            fn () => new \App\Console\Commands\InstallJwtRefreshCommand($this->app->basePath('database/migrations')));
    }

    public function boot(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => (int) config('session.cookie_lifetime', 0),
            'path' => (string) config('session.cookie_path', '/'),
            'domain' => config('session.cookie_domain'),
            'secure' => (bool) config('session.cookie_secure', false),
            'httponly' => true,
            'samesite' => (string) config('session.cookie_samesite', 'Lax'),
        ]);
    }
}
