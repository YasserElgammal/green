<?php

namespace App\Controllers;

use App\Tables\CommentTable;
use App\Tables\PostTable;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Routing\Route;

class CommentController
{
    #[Route('POST', '/posts/{postId}/comments')]
    public function store(int $postId, Request $request)
    {
        if (!session()->has('user_id')) {
            session()->flash('error', 'You must be logged in to comment.');
            return redirect('/login');
        }

        $content = $request->input('content');

        if (!$content) {
            session()->flash('error', 'Comment cannot be empty.');
            return redirect("/posts/{$postId}");
        }

        $postsTable = new PostTable();
        $post = $postsTable->fetchById($postId);

        if (!$post) {
            session()->flash('error', 'Post not found.');
            return redirect('/posts');
        }

        $commentsTable = new CommentTable();
        $commentsTable->insert([
            'post_id' => $postId,
            'user_id' => session()->get('user_id'),
            'content' => $content,
        ]);

        session()->flash('success', 'Comment added.');
        return redirect("/posts/{$postId}");
    }
}
