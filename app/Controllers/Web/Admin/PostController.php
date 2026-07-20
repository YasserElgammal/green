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
        $posts = new PostTable();
        $posts->include(['author']);
        $posts->includeCount('comments');
        $query = $posts->query()->latest('id');

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $authorIds = array_map(
                static fn ($user) => $user->id,
                (new UserTable())->query()->whereLike('name', "%{$search}%")->fetch()
            );

            $query->whereGroup(fn ($query) => $query
                ->whereLike('title', "%{$search}%")
                ->orWhereLike('status', "%{$search}%")
                ->orWhereIn('user_id', $authorIds));
        }

        $result = $query->paginate(self::PER_PAGE, $this->page());

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
            return $users->query()->orderBy('name')->fetch();
        } catch (\Throwable) {
            return [];
        }
    }

}
