<?php

namespace Tests\Web\Controllers;

use App\Controllers\Web\CommentController;
use App\Controllers\Web\PostController;
use App\Tables\CommentTable;
use App\Tables\PostTable;
use App\Tables\LikeTable;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\View\View;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    private CommentController $commentController;
    private PostController $postController;
    private CommentTable $commentTable;
    private PostTable $postTable;
    private LikeTable $likeTable;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize Twig Views
        View::init(dirname(__DIR__, 3) . '/views');

        $this->commentController = new CommentController();
        $this->postController = new PostController();
        $this->commentTable = new CommentTable();
        $this->postTable = new PostTable();
        $this->likeTable = new LikeTable();
    }

    public function testLikeCommentTogglesAndAggregatesCorrectly(): void
    {
        $this->seed();

        // 1. Create a comment on the seeded post (post_id = 1, user_id = 1)
        $comment = $this->commentTable->insert([
            'post_id' => 1,
            'user_id' => 1,
            'content' => 'First test comment'
        ]);

        // 2. Set up authenticated session
        session()->put('user_id', 1);

        // 3. Attempt to like the comment via CommentController
        $request = new Request();
        $response = $this->commentController->like($comment->id, $request);

        // Assert redirect response back to post details page
        $this->assertInstanceOf(\YasserElgammal\Green\Http\RedirectResponse::class, $response);
        
        $ref = new \ReflectionClass($response);
        $headersProp = $ref->getParentClass()->getProperty('headers');
        $headersProp->setAccessible(true);
        $headers = $headersProp->getValue($response);
        $this->assertEquals('/posts/1', $headers['Location']);

        // Assert a like record was created
        $likesCount = $this->likeTable->count();
        $this->assertEquals(1, $likesCount);

        // Assert success message is flashed
        $this->assertEquals(['Comment liked!'], session()->getFlash('success'));

        // 4. Verify that PostController show method fetches comment likes count using includeCount / IQL
        $postResponse = $this->postController->show(1);
        $this->assertIsString($postResponse);

        // Fetch post using PostTable directly to verify aggregation
        $postTable = new PostTable();
        $postTable->include('comments.likes(count)');
        $post = $postTable->fetchById(1);

        $this->assertNotEmpty($post->comments);
        $loadedComment = $post->comments[0];
        $this->assertEquals(1, $loadedComment->likes_count);

        // 5. Toggle Like again (Unlike action)
        session()->put('user_id', 1);
        $response = $this->commentController->like($comment->id, $request);

        // Assert unliked successfully
        $this->assertEquals(0, $this->likeTable->count());
        $this->assertEquals(['Comment unliked.'], session()->getFlash('success'));

        // 6. Verify likes count is now 0 on post details
        $postTable = new PostTable();
        $postTable->include('comments.likes(count)');
        $post = $postTable->fetchById(1);
        $this->assertEquals(0, $post->comments[0]->likes_count);
    }
}
