<?php

namespace Tests\Controllers;

use App\Controllers\ApiController;
use YasserElgammal\Green\Http\Request;
use Tests\TestCase;

class ApiControllerTest extends TestCase
{
    private ApiController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ApiController();
    }

    public function testIndexReturnsAllUsers(): void
    {
        $this->seed();

        $request = new Request();
        $response = $this->controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(2, $data['data']);
        $this->assertEquals('Test User', $data['data'][0]['name']);
    }

    public function testIndexSupportsPagination(): void
    {
        $this->seed();
        // Add more users to test pagination
        for ($i = 0; $i < 20; $i++) {
            $this->connection->insert('users', [
                'name' => "User $i", 
                'email' => "user$i@example.com",
                'password' => password_hash('password', PASSWORD_DEFAULT)
            ]);
        }

        $request = new Request(['page' => 1, 'per_page' => 5]);
        $response = $this->controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertCount(5, $data['data']);
        $this->assertEquals(22, $data['meta']['total_items']);
    }

    public function testShowReturnsSingleUser(): void
    {
        $this->seed();

        $request = new Request();
        $response = $this->controller->show(1, $request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals(1, $data['data']['id']);
        $this->assertEquals('Test User', $data['data']['name']);
    }

    public function testStoreCreatesUser(): void
    {
        $request = new Request([], [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
        ]);

        $response = $this->controller->store($request);

        $this->assertEquals(201, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('New User', $data['data']['name']);
        
        // Verify in database
        $user = $this->connection->fetchAssociative("SELECT * FROM users WHERE email = 'new@example.com'");
        $this->assertNotEmpty($user);
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
        $this->assertEquals('Test User', $data['data']['name']);
    }

    public function testUpdateModifiesUser(): void
    {
        $this->seed();

        $request = new Request([], [
            'name' => 'Updated Name'
        ]);

        $response = $this->controller->update(1, $request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('Updated Name', $data['data']['name']);

        // Verify in database
        $user = $this->connection->fetchAssociative("SELECT * FROM users WHERE id = 1");
        $this->assertEquals('Updated Name', $user['name']);
    }

    public function testDestroyDeletesUser(): void
    {
        $this->seed();

        $response = $this->controller->destroy(1);

        $this->assertEquals(200, $response->getStatusCode());
        
        // Verify in database
        $user = $this->connection->fetchAssociative("SELECT * FROM users WHERE id = 1");
        $this->assertFalse($user);
    }

    public function testPostsReturnsAllPosts(): void
    {
        $this->seed();

        $request = new Request();
        $response = $this->controller->posts($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(1, $data['data']);
        $this->assertEquals('Test Post', $data['data'][0]['title']);
    }
}
