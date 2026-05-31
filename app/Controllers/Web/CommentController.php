<?php

namespace App\Controllers\Web;

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

    #[Route('POST', '/comments/{commentId}/like')]
    public function like(int $commentId, Request $request)
    {
        if (!session()->has('user_id')) {
            session()->flash('error', 'You must be logged in to like comments.');
            return redirect('/login');
        }

        $commentsTable = new CommentTable();
        $comment = $commentsTable->fetchById($commentId);

        if (!$comment) {
            session()->flash('error', 'Comment not found.');
            return redirect('/posts');
        }

        $likesTable = new \App\Tables\LikeTable();
        $userId = session()->get('user_id');

        // Check if already liked
        $qb = $likesTable->builder()
            ->where('comment_id = :comment_id')
            ->andWhere('user_id = :user_id')
            ->setParameter('comment_id', $commentId)
            ->setParameter('user_id', $userId);

        $existing = $likesTable->fetchFromBuilder($qb);

        if (!empty($existing)) {
            // Unlike
            $likesTable->delete($existing[0]);
            session()->flash('success', 'Comment unliked.');
        } else {
            // Like
            $likesTable->insert([
                'comment_id' => $commentId,
                'user_id' => $userId,
            ]);
            session()->flash('success', 'Comment liked!');
        }

        return redirect("/posts/{$comment->post_id}");
    }
}
