<?php

namespace App\Controllers\Web;

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

        $postsQuery = $postsTable->builder()->orderBy('id', strtoupper($order));
        $result  = $postsTable->paginateFromBuilder($postsQuery, (int) $perPage, (int) ($_GET['page'] ?? 1));

        return view('posts/index', [
            'posts' => $result['data'],
            'meta'  => $result['meta'],
            'order' => $order
        ]);
    }

    #[Route('GET', '/posts/{id}')]
    public function show(int $id)
    {
        $postsTable = new PostTable();
        $postsTable->include(['author', 'comments.author']);

        $post = $postsTable->fetchById($id);

        if (!$post) {
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

        $postsTable = new PostTable();
        $postsTable->insert([
            'title' => $title,
            'body' => $body,
            'image' => $imagePath,
            'user_id' => session()->get('user_id'),
        ]);

        session()->flash('success', 'Post created successfully!');
        return redirect('/posts');
    }
}
