<?php

namespace App\Controllers;

use App\Tables\PostTable;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Routing\Route;

class PostController
{
    #[Route('GET', '/posts')]
    public function index()
    {
        $postsTable = new PostTable();
        // Load author and comments for eager loading efficiency
        $postsTable->include(['author']);
        
        $postsQuery = $postsTable->builder()->orderBy('id', 'DESC');
        $posts = $postsTable->fetchFromBuilder($postsQuery);

        return view('posts/index', ['posts' => $posts]);
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

        $postsTable = new PostTable();
        $postsTable->insert([
            'title' => $title,
            'body' => $body,
            'user_id' => session()->get('user_id'),
        ]);

        session()->flash('success', 'Post created successfully!');
        return redirect('/posts');
    }
}
