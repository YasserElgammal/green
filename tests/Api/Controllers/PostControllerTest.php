<?php

namespace Tests\Api\Controllers;

use App\Controllers\Api\PostController;
use YasserElgammal\Green\Http\Request;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    private PostController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new PostController();
    }

    public function testPostsReturnsAllPosts(): void
    {
        $this->seed();

        $request = new Request();
        $response = $this->controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getContent(), true);

        $this->assertCount(1, $data['data']);
        $this->assertEquals('Test Post', $data['data'][0]['title']);
    }

    public function testShowReturnsSinglePost(): void
    {
        $this->seed();

        $response = $this->controller->show(1);

        $data = json_decode($response->getContent(), true);

        $this->assertEquals(1, $data['data']['id']);
    }
}
