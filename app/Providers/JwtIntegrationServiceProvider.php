<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\UserIdentityResolver;
use YasserElgammal\Green\AuthJwt\IdentityResolverInterface;
use YasserElgammal\Green\AuthJwt\JwtServiceProvider;
use YasserElgammal\Green\Support\ServiceProvider;

final class JwtIntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (!config('auth.jwt.enabled', false)) {
            return;
        }
        if (!class_exists(JwtServiceProvider::class)) {
            throw new \LogicException('JWT is enabled: install yasser-elgammal/green-auth-jwt first.');
        }
        $this->app->bind(IdentityResolverInterface::class, UserIdentityResolver::class);
        $this->app->register(new JwtServiceProvider($this->app));
        if (config('auth.jwt.refresh_tokens', false)) {
            $this->app->register(new \YasserElgammal\Green\AuthJwt\Refresh\RefreshTokenServiceProvider($this->app));
        }
    }
}
