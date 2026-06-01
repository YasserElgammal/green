<?php

namespace App\Controllers\Web\Admin;

use App\Enums\PostStatus;
use App\Middleware\AdminMiddleware;
use App\Payloads\AdminPostPayload;
use App\Tables\PostTable;
use App\Tables\UserTable;
use YasserElgammal\Green\Routing\Route;

class PostController extends BaseAdminController
{
    #[Route('GET', '/admin/posts', [AdminMiddleware::class])]
    public function index()
    {
        $search = $this->query('search');
        $status = $this->query('status');
        $status = $status === '' ? '' : PostStatus::fromRequest($status)->value;
        $result = $this->paginateArray([], self::PER_PAGE, 1);

        try {
            $posts = new PostTable();
            $posts->include(['author']);
            $posts->includeCount('comments');
            $query = $posts->builder()
                ->orderBy('id', 'DESC');

            if ($status !== '') {
                $query->where('status = :status')
                    ->setParameter('status', $status);
            }

            if ($search !== '') {
                $posts = $this->filterPosts($posts->fetchFromBuilder($query), $search);
                $result = $this->paginateArray($posts, self::PER_PAGE, $this->page());
            } else {
                $result = $posts->paginateFromBuilder($query, self::PER_PAGE, $this->page());
            }
        } catch (\Throwable) {
            session()->flash('error', 'Unable to load posts. Make sure migrations are up to date.');
        }

        return view('admin/posts/index', [
            'posts' => $result['data'],
            'meta' => $result['meta'],
            'search' => $search,
            'status' => $status,
            'statuses' => PostStatus::options(),
        ]);
    }

    #[Route('GET', '/admin/posts/create', [AdminMiddleware::class])]
    public function create()
    {
        return view('admin/posts/form', [
            'post' => null,
            'mode' => 'create',
            'users' => $this->usersForSelect(),
            'statuses' => PostStatus::options(),
        ]);
    }

    #[Route('POST', '/admin/posts', [AdminMiddleware::class])]
    public function store(AdminPostPayload $payload)
    {
        $data = $payload->validated();

        (new PostTable())->insert([
            'title' => $data['title'],
            'body' => $data['body'],
            'user_id' => (int) $data['user_id'],
            'status' => $data['status'],
        ]);

        session()->flash('success', 'Post created successfully.');
        return redirect('/admin/posts');
    }

    #[Route('GET', '/admin/posts/{id}/edit', [AdminMiddleware::class])]
    public function edit(int $id)
    {
        $post = (new PostTable())->fetchById($id);

        if (!$post) {
            session()->flash('error', 'Post not found.');
            return redirect('/admin/posts');
        }

        return view('admin/posts/form', [
            'post' => $post,
            'mode' => 'edit',
            'users' => $this->usersForSelect(),
            'statuses' => PostStatus::options(),
        ]);
    }

    #[Route('POST', '/admin/posts/{id}', [AdminMiddleware::class])]
    public function update(int $id, AdminPostPayload $payload)
    {
        $data = $payload->validated();

        (new PostTable())->update($id, [
            'title' => $data['title'],
            'body' => $data['body'],
            'user_id' => (int) $data['user_id'],
            'status' => $data['status'],
        ]);

        session()->flash('success', 'Post updated successfully.');
        return redirect('/admin/posts');
    }

    #[Route('POST', '/admin/posts/{id}/delete', [AdminMiddleware::class])]
    public function delete(int $id)
    {
        (new PostTable())->deleteById($id);
        session()->flash('success', 'Post deleted successfully.');
        return redirect('/admin/posts');
    }

    private function usersForSelect(): array
    {
        try {
            $users = new UserTable();
            return $users->fetchFromBuilder($users->builder()->orderBy('name', 'ASC'));
        } catch (\Throwable) {
            return [];
        }
    }

    private function filterPosts(array $posts, string $search): array
    {
        $needle = strtolower($search);

        return array_values(array_filter($posts, function ($post) use ($needle) {
            $author = $post->author->name ?? '';

            return str_contains(strtolower((string) $post->title), $needle)
                || str_contains(strtolower((string) $post->status), $needle)
                || str_contains(strtolower((string) $author), $needle);
        }));
    }
}
