<?php

namespace App\Controllers\Web\Admin;

use App\Middleware\AdminMiddleware;
use App\Tables\CommentTable;
use App\Tables\PostTable;
use App\Tables\UserTable;
use YasserElgammal\Green\Routing\Route;

class StatisticsController extends BaseAdminController
{
    #[Route('GET', '/admin/statistics', [AdminMiddleware::class])]
    public function statistics()
    {
        return view('admin/statistics', [
            'stats' => $this->dashboardStats() + [
                'new_users_month' => $this->countThisMonth('users'),
                'posts_month' => $this->countThisMonth('posts'),
                'comments_month' => $this->countThisMonth('comments'),
            ],
            'latestUsers' => $this->latestUsers(6),
            'latestPosts' => $this->latestPosts(6),
            'latestComments' => $this->latestComments(6),
        ]);
    }

    public function dashboardStats(): array
    {
        return [
            'users' => $this->safeCount(UserTable::class),
            'admins' => $this->adminCount(),
            'posts' => $this->safeCount(PostTable::class),
            'comments' => $this->safeCount(CommentTable::class),
        ];
    }

    public function latestUsers(int $limit): array
    {
        try {
            $users = new UserTable();
            $users->includeCount('posts');
            return $users->query()->latest('id')->limit($limit)->fetch();
        } catch (\Throwable) {
            return [];
        }
    }

    public function latestPosts(int $limit): array
    {
        try {
            $posts = new PostTable();
            $posts->include(['author']);
            $posts->includeCount('comments');
            return $posts->query()->latest('id')->limit($limit)->fetch();
        } catch (\Throwable) {
            return [];
        }
    }

    public function latestComments(int $limit): array
    {
        try {
            $comments = new CommentTable();
            $comments->include(['author', 'post']);
            return $comments->query()->latest('id')->limit($limit)->fetch();
        } catch (\Throwable) {
            return [];
        }
    }

    private function safeCount(string $tableClass): int
    {
        try {
            $table = new $tableClass();
            return $table->query()->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function adminCount(): int
    {
        try {
            return (new UserTable())->query()->where('is_admin', 1)->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function countThisMonth(string $table): int
    {
        try {
            $tableClass = match ($table) {
                'users' => UserTable::class,
                'posts' => PostTable::class,
                'comments' => CommentTable::class,
            };

            return (new $tableClass())
                ->query()
                ->where('created_at', '>=', date('Y-m-01 00:00:00'))
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
