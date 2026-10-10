<?php

namespace Tests\Api\Controllers;

use App\Controllers\Api\AuthController;
use App\Payloads\LoginPayload;
use App\Payloads\RegisterPayload;
use App\Tables\UserTable;
use Tests\TestCase;
use YasserElgammal\Green\Http\Request;

class AuthControllerTest extends TestCase
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
        $refreshToken = auth()->issueRefreshToken($user);

        $response = $this->controller->refresh(new Request([], [
            'refresh_token' => $refreshToken,
        ]));
        $data = $this->json($response->getContent());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('access_token', $data['data']);
        $this->assertNotSame($refreshToken, $data['data']['refresh_token']);
    }

    public function testRefreshRejectsInvalidToken(): void
    {
        $response = $this->controller->refresh(new Request([], [
            'refresh_token' => 'invalid-token',
        ]));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertFalse($this->json($response->getContent())['success']);
    }

    private function json(string $content): array
    {
        return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    }
}
