<?php

namespace Tests\Jwt\Services;

use Tests\JwtTestCase;
use App\Controllers\Api\AuthController;
use App\Payloads\LoginPayload;
use YasserElgammal\Green\Http\Request;

final class JwtWithoutRefreshTest extends JwtTestCase
{
    protected bool $refreshEnabled = false;

    public function testJwtLoginDoesNotRequireRefreshStorage(): void
    {
        $this->connection->executeStatement('DROP TABLE users');
        $this->connection->executeStatement('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, password TEXT, created_at DATETIME, updated_at DATETIME)');
        $this->createUser();
        $controller = new AuthController();
        $response = $controller->login(new LoginPayload(new Request(post: [
            'email' => 'user@example.com', 'password' => 'password',
        ])));
        self::assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR)['data'];
        self::assertIsString($data['access_token']);
        self::assertNull($data['refresh_token']);
        self::assertSame(404, $controller->refresh(new Request())->getStatusCode());
    }
}
