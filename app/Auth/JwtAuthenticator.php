<?php

declare(strict_types=1);

namespace App\Auth;

use YasserElgammal\Green\Auth\Authenticator;
use YasserElgammal\Green\AuthJwt\JwtAuthenticator as PackageAuthenticator;
use YasserElgammal\Green\Http\Request;

/** Application compatibility adapter; instantiated only when JWT is enabled. */
final class JwtAuthenticator implements Authenticator
{
    public function __construct(private readonly PackageAuthenticator $authenticator)
    {
    }

    public function authenticate(Request $request): ?object
    {
        return $this->authenticator->authenticate($request);
    }
}
