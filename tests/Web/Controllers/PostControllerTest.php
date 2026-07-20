<?php

namespace Tests\Web\Controllers;

use App\Controllers\Web\PostController;
use App\Tables\PostTable;
use Tests\TestCase;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Http\RedirectResponse;
use YasserElgammal\Green\View\View;

class PostControllerTest extends TestCase
{
    private PostController $controller;
    private PostTable $postTable;

    protected function setUp(): void
    {
        parent::setUp();

        View::init(dirname(__DIR__, 3) . '/views');

        $this->controller = new PostController();
        $this->postTable = new PostTable();
        $_FILES = [];
    }

    protected function tearDown(): void
    {
        $_GET = [];
        parent::tearDown();
    }

    public function testIndexUsesPublishedFilterAndRequestedOrder(): void
    {
        $this->seed();
        $this->postTable->insert([
            'user_id' => 1,
            'title' => 'Newest Published',
            'status' => 'published',
            'body' => 'Visible.',
        ]);
        $this->postTable->insert([
            'user_id' => 1,
            'title' => 'Hidden Draft',
            'status' => 'draft',
            'body' => 'Hidden.',
        ]);
        $_GET = ['order' => 'ASC', 'per_page' => 1];

        $response = $this->controller->index();

        $this->assertStringContainsString('Test Post', $response);
        $this->assertStringNotContainsString('Newest Published', $response);
        $this->assertStringNotContainsString('Hidden Draft', $response);
    }

    public function testStoreCanSaveUserPostAsDraft(): void
    {
        $this->seed();
        session()->put('user_id', 1);

        $response = $this->controller->store(new Request([], [
            'title' => 'Draft Post',
            'body' => 'Draft body.',
            'status' => 'draft',
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);

        $post = $this->postTable->fetchFirst('title', 'Draft Post');
        $this->assertNotNull($post);
        $this->assertEquals('draft', $post->status);
        $this->assertEquals(1, (int) $post->user_id);
    }

    public function testMyPostsListsOnlyAuthenticatedUsersSelectedStatus(): void
    {
        $this->seed();
        session()->put('user_id', 1);

        $this->postTable->insert([
            'user_id' => 1,
            'title' => 'My Draft',
            'status' => 'draft',
            'body' => 'Visible draft.',
        ]);
        $this->postTable->insert([
            'user_id' => 2,
            'title' => 'Other Draft',
            'status' => 'draft',
            'body' => 'Hidden draft.',
        ]);

        $_GET['status'] = 'draft';
        $response = $this->controller->myPosts();
        unset($_GET['status']);

        $this->assertIsString($response);
        $this->assertStringContainsString('My Draft', $response);
        $this->assertStringNotContainsString('Other Draft', $response);
    }

    public function testUserCanOnlyChangeStatusForOwnPost(): void
    {
        $this->seed();
        session()->put('user_id', 1);

        $ownPost = $this->postTable->insert([
            'user_id' => 1,
            'title' => 'Own Draft',
            'status' => 'draft',
            'body' => 'Can publish.',
        ]);
        $otherPost = $this->postTable->insert([
            'user_id' => 2,
            'title' => 'Other Draft',
            'status' => 'draft',
            'body' => 'Cannot publish.',
        ]);

        $this->controller->updateStatus($ownPost->id, new Request([], ['status' => 'published']));
        $this->controller->updateStatus($otherPost->id, new Request([], ['status' => 'published']));

        $this->assertEquals('published', $this->postTable->fetchById($ownPost->id)->status);
        $this->assertEquals('draft', $this->postTable->fetchById($otherPost->id)->status);
    }
}
