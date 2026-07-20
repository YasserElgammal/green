<?php

namespace Tests\Web\Controllers;

use App\Controllers\Web\Admin\DashboardController;
use App\Controllers\Web\Admin\CommentController;
use App\Controllers\Web\Admin\PostController;
use App\Controllers\Web\Admin\StatisticsController;
use App\Controllers\Web\Admin\UserController;
use Tests\TestCase;
use YasserElgammal\Green\View\View;

class AdminControllerTest extends TestCase
{
    private DashboardController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        View::init(dirname(__DIR__, 3) . '/views');
        $this->seed();
        $this->controller = new DashboardController();
    }

    protected function tearDown(): void
    {
        $_GET = [];
        parent::tearDown();
    }

    public function testDashboardReturnsRenderedAdminView(): void
    {
        $response = $this->controller->dashboard();

        $this->assertIsString($response);
        $this->assertStringContainsString('Admin Dashboard', $response);
        $this->assertStringContainsString('Total Users', $response);
        $this->assertStringContainsString('test@example.com', $response);
    }

    public function testUserIndexSearchesNameAndEmailWithPagination(): void
    {
        $_GET = ['search' => 'admin@example.com'];

        $response = (new UserController())->index();

        $this->assertStringContainsString('Admin User', $response);
        $this->assertStringNotContainsString('test@example.com', $response);
    }

    public function testStatisticsUseFilteredCountsAndLatestLimits(): void
    {
        $this->connection->insert('users', [
            'name' => 'Second Admin',
            'email' => 'second-admin@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'is_admin' => 1,
        ]);

        $statistics = new StatisticsController();

        $this->assertSame(3, $statistics->dashboardStats()['users']);
        $this->assertSame(2, $statistics->dashboardStats()['admins']);
        $this->assertCount(2, $statistics->latestUsers(2));
        $this->assertSame('Second Admin', $statistics->latestUsers(1)[0]->name);
    }

    public function testPostSearchFiltersByRelatedAuthorBeforePagination(): void
    {
        $_GET = ['search' => 'Test User'];

        $response = (new PostController())->index();

        $this->assertStringContainsString('Test Post', $response);
    }

    public function testCommentSearchFiltersByRelatedPostBeforePagination(): void
    {
        $this->connection->insert('comments', [
            'post_id' => 1,
            'user_id' => 2,
            'content' => 'A comment with unrelated content',
        ]);
        $_GET = ['search' => 'Test Post'];

        $response = (new CommentController())->index();

        $this->assertStringContainsString('A comment with unrelated content', $response);
    }
}
