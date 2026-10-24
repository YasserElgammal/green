<?php

namespace Tests;

abstract class JwtTestCase extends TestCase
{
    protected bool $jwtEnabled = true;

    protected function setUp(): void
    {
        if (!class_exists(\YasserElgammal\Green\AuthJwt\JwtService::class)) {
            $this->markTestSkipped('yasser-elgammal/green-auth-jwt is not installed.');
        }

        parent::setUp();
    }
}

