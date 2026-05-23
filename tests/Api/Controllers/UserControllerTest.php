<?php

namespace Tests\Api\Controllers;

use App\Controllers\Api\UserController;
use YasserElgammal\Green\Http\Request;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    private UserController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new UserController();
    }

    public function testIndexReturnsAllUsers(): void
    {
        $this->seed();

        $request = new Request();
        $response = $this->controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertCount(2, $data['data']['items']);
    }

    public function testIndexSupportsPagination(): void
    {
        $this->seed();

        for ($i = 0; $i < 20; $i++) {
            $this->connection->insert('users', [
                'name' => "User $i",
                'email' => "user$i@example.com",
                'password' => password_hash('password', PASSWORD_DEFAULT)
            ]);
        }

        $request = new Request(['page' => 1, 'per_page' => 5]);
        $response = $this->controller->index($request);

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertCount(5, $data['data']['items']);
        $this->assertEquals(22, $data['data']['meta']['total_items']);
    }

    public function testShowReturnsSingleUser(): void
    {
        $this->seed();

        $response = $this->controller->show(1);

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals(1, $data['data']['item']['id']);
    }

    public function testStoreCreatesUser(): void
    {
        $request = new Request([], [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password',
        ]);

        $response = $this->controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());

        $user = $this->connection->fetchAssociative(
            "SELECT * FROM users WHERE email = 'new@example.com'"
        );

        $this->assertNotEmpty($user);
    }

    public function testUpdateModifiesUser(): void
    {
        $this->seed();

        $request = new Request([], [
            'name' => 'Updated Name'
        ]);

        $response = $this->controller->update(1, $request);

        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Updated Name', $data['data']['item']['name']);
    }

    public function testDestroyDeletesUser(): void
    {
        $this->seed();

        $this->controller->destroy(1);

        $user = $this->connection->fetchAssociative(
            "SELECT * FROM users WHERE id = 1"
        );

        $this->assertFalse($user);
    }
}
