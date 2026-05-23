<?php

namespace Tests\Api\Controllers;

use App\Controllers\Api\AuthController;
use App\Payloads\RegisterPayload;
use YasserElgammal\Green\Http\Request;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    private AuthController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new AuthController();
    }

    public function testLogin(): void
    {
        $this->seed();

        $request = new Request([], [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response = $this->controller->login($request);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('access_token', $data['data']);
        $this->assertArrayHasKey('refresh_token', $data['data']);
        $this->assertEquals('Bearer', $data['data']['token_type']);
    }

    public function testLoginFails(): void
    {
        $this->seed();

        $request = new Request([], [
            'email' => 'test@example.com',
            'password' => 'wrong',
        ]);

        $response = $this->controller->login($request);

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testRegister(): void
    {
        $request = new Request([], [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => '123456',
        ]);

        $payload = new RegisterPayload($request);

        $response = $this->controller->register($payload);

        $this->assertEquals(201, $response->getStatusCode());

        $data = json_decode((string) $response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('User registered successfully!', $data['message']);
        $this->assertEquals('New User', $data['data']['user']['name']);
        $this->assertEquals('new@example.com', $data['data']['user']['email']);
    }
}
