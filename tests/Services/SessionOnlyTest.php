<?php

namespace Tests\Services;

use Tests\TestCase;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Auth\Auth;

final class SessionOnlyTest extends TestCase
{
    public function testDefaultApplicationDoesNotRegisterJwtRoutesOrServices(): void
    {
        self::assertFalse($this->app->has(\YasserElgammal\Green\AuthJwt\JwtService::class));
        foreach (['/api/login', '/api/profile', '/api/refresh'] as $path) {
            $response = $this->app->handle(new Request(server: ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => $path]));
            self::assertSame(404, $response->getStatusCode());
        }
        self::assertInstanceOf(Auth::class, auth());
    }

    public function testSessionPasswordChangeWorksWithoutRefreshColumns(): void
    {
        $this->connection->executeStatement('DROP TABLE users');
        $this->connection->executeStatement('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, password TEXT, created_at DATETIME, updated_at DATETIME)');
        $user = $this->createUser();
        self::assertTrue((new \App\Services\ProfileService())->changePassword($user->id, 'password', 'next-password'));
    }
}
