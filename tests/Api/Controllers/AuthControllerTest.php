<?php

namespace Tests\Jwt\Api\Controllers;

use App\Controllers\Api\AuthController;
use App\Payloads\LoginPayload;
use App\Payloads\RegisterPayload;
use App\Tables\UserTable;
use Tests\JwtTestCase;
use YasserElgammal\Green\Http\Request;

class AuthControllerTest extends JwtTestCase
{
    private AuthController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new AuthController();
    }

    public function testUserCanRegister(): void
    {
        $payload = new RegisterPayload(new Request([], [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password123',
        ]));

        $response = $this->controller->register($payload);
        $data = $this->json($response->getContent());

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertSame('new@example.com', $data['data']['user']['email']);
        $this->assertNotNull((new UserTable())->fetchFirst('email', 'new@example.com'));
    }

    public function testUserCanLoginAndReceiveTokens(): void
    {
        $this->createUser();
        $payload = new LoginPayload(new Request([], [
            'email' => 'user@example.com',
            'password' => 'password',
        ]));

        $response = $this->controller->login($payload);
        $data = $this->json($response->getContent());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('access_token', $data['data']);
        $this->assertArrayHasKey('refresh_token', $data['data']);
        $this->assertSame('Bearer', $data['data']['token_type']);
    }

    public function testLoginRejectsInvalidCredentials(): void
    {
        $this->createUser();
        $payload = new LoginPayload(new Request([], [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]));

        $response = $this->controller->login($payload);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertFalse($this->json($response->getContent())['success']);
    }

    public function testRefreshRotatesTokens(): void
    {
        $user = $this->createUser();
        $refreshToken = app(\App\Services\JwtTokenService::class)->issueRefreshToken($user);

        $response = $this->controller->refresh(new Request([], [
            'refresh_token' => $refreshToken,
        ]));
        $data = $this->json($response->getContent());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('access_token', $data['data']);
        $this->assertNotSame($refreshToken, $data['data']['refresh_token']);
        $this->assertNull(app(\App\Services\JwtTokenService::class)->verifyRefreshToken($refreshToken));
        $this->assertSame(401, $this->controller->refresh(new Request([], [
            'refresh_token' => $refreshToken,
        ]))->getStatusCode());
    }

    public function testRefreshRejectsInvalidToken(): void
    {
        $response = $this->controller->refresh(new Request([], [
            'refresh_token' => 'invalid-token',
        ]));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertFalse($this->json($response->getContent())['success']);
    }

    public function testRefreshRejectsAnAccessToken(): void
    {
        $user = $this->createUser();
        $response = $this->controller->refresh(new Request([], [
            'refresh_token' => app(\App\Services\JwtTokenService::class)->issueToken($user),
        ]));

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testRefreshRejectsArrayInputWithoutServerError(): void
    {
        $response = $this->controller->refresh(new Request([], [
            'refresh_token' => ['unexpected'],
        ]));

        $this->assertSame(422, $response->getStatusCode());
    }

    public function testRefreshTokenIsHashedAndExpires(): void
    {
        $user = $this->createUser();
        $plainToken = app(\App\Services\JwtTokenService::class)->issueRefreshToken($user);
        $stored = $this->connection->fetchAssociative('SELECT * FROM refresh_tokens WHERE modelable_id = ?', [(string) $user->id]);

        $this->assertStringStartsWith('green_refresh_', $plainToken);
        $this->assertNotSame($plainToken, $stored['token_hash']);
        $this->assertSame(hash('sha256', $plainToken), $stored['token_hash']);

        $this->connection->update('refresh_tokens', [
            'expires_at' => gmdate('Y-m-d H:i:s', time() - 1),
        ], ['id' => $stored['id']]);

        $this->assertNull(app(\App\Services\JwtTokenService::class)->verifyRefreshToken($plainToken));
    }

    private function json(string $content): array
    {
        return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    }
}
