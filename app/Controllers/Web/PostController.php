<?php

namespace App\Controllers\Web;

use App\Enums\PostStatus;
use App\Tables\PostTable;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Routing\Route;
use YasserElgammal\Green\Drive\Drive;

class PostController
{
    #[Route('GET', '/posts')]
    public function index()
    {
        $order = in_array($_GET['order'] ?? '', ['ASC', 'DESC']) ? $_GET['order'] : 'DESC';
        $perPage  = (int) ($_GET['per_page'] ?? 15);

        $postsTable = new PostTable();
        // Load author and comments for eager loading efficiency
        $postsTable->include(['author']);

        $result = $postsTable->query()
            ->where('status', PostStatus::Published->value)
            ->orderBy('id', $order)
            ->paginate($perPage, (int) ($_GET['page'] ?? 1));

        return view('posts/index', [
            'posts' => $result['data'],
            'meta'  => $result['meta'],
            'order' => $order
        ]);
    }

    #[Route('GET', '/my-posts')]
    public function myPosts()
    {
        if (!session()->has('user_id')) {
            session()->flash('error', 'You must be logged in to manage your posts.');
            return redirect('/login');
        }

        $activeStatus = PostStatus::fromRequest($_GET['status'] ?? PostStatus::Draft->value);
        $perPage = (int) ($_GET['per_page'] ?? 15);

        $postsTable = (new PostTable())->includeCount('comments');
        $result = $postsTable->query()
            ->where([
                'user_id' => session()->get('user_id'),
                'status' => $activeStatus->value,
            ])
            ->latest('id')
            ->paginate($perPage, (int) ($_GET['page'] ?? 1));

        return view('posts/my', [
            'posts' => $result['data'],
            'meta' => $result['meta'],
            'activeStatus' => $activeStatus->value,
            'statuses' => PostStatus::options(),
        ]);
    }

    #[Route('GET', '/posts/{id}')]
    public function show(int $id)
    {
        $postsTable = new PostTable();
        $postsTable->include('author,comments.author,comments.likes(count)');

        $post = $postsTable->fetchById($id);

        if (!$post || $post->status !== PostStatus::Published->value) {
            session()->flash('error', 'Post not found.');
            return redirect('/posts');
        }

        return view('posts/show', ['post' => $post]);
    }

    #[Route('POST', '/posts')]
    public function store(Request $request)
    {
        if (!session()->has('user_id')) {
            session()->flash('error', 'You must be logged in to post.');
            return redirect('/login');
        }

        $title = $request->input('title');
        $body = $request->input('body');

        if (!$title || !$body) {
            session()->flash('error', 'Title and body are required.');
            return redirect('/posts');
        }

        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $fileContent = file_get_contents($_FILES['image']['tmp_name']);
            $filename = 'uploads/posts/' . uniqid() . '_' . basename($_FILES['image']['name']);
            drive()->put($filename, $fileContent);
            $imagePath = $filename;
        }

        $status = PostStatus::tryFrom((string) $request->input('status', PostStatus::Published->value))
            ?? PostStatus::Published;

        $postsTable = new PostTable();
        $postsTable->insert([
            'title' => $title,
            'body' => $body,
            'image' => $imagePath,
            'user_id' => session()->get('user_id'),
            'status' => $status->value,
        ]);

        session()->flash('success', $status === PostStatus::Draft ? 'Post saved as draft.' : 'Post created successfully!');
        return redirect($status === PostStatus::Draft ? '/my-posts?status=draft' : '/posts');
    }

    #[Route('POST', '/my-posts/{id}/status')]
    public function updateStatus(int $id, Request $request)
    {
        if (!session()->has('user_id')) {
            session()->flash('error', 'You must be logged in to manage your posts.');
            return redirect('/login');
        }

        $status = PostStatus::fromRequest($request->input('status'));
        $postsTable = new PostTable();
        $post = $postsTable->fetchById($id);

        if (!$post || (int) $post->user_id !== (int) session()->get('user_id')) {
            session()->flash('error', 'Post not found.');
            return redirect('/my-posts');
        }

        $postsTable->update($id, ['status' => $status->value]);

        session()->flash('success', 'Post moved to ' . $status->label() . '.');
        return redirect('/my-posts?status=' . $status->value);
    }
}
