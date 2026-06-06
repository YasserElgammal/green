<?php

namespace App\Controllers\Web\Admin;

use App\Middleware\AdminMiddleware;
use App\Tables\CommentTable;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Routing\Route;

class CommentController extends BaseAdminController
{
    #[Route('GET', '/admin/comments', [AdminMiddleware::class])]
    public function index()
    {
        $search = $this->query('search');
        $result = $this->paginateArray([], self::PER_PAGE, 1);

        try {
            $comments = new CommentTable();
            $comments->include(['author', 'post']);
            $query = $comments->builder()
                ->orderBy('id', 'DESC');

            if ($search !== '') {
                $comments = $this->filterComments($comments->fetchFromBuilder($query), $search);
                $result = $this->paginateArray($comments, self::PER_PAGE, $this->page());
            } else {
                $result = $comments->paginateFromBuilder($query, self::PER_PAGE, $this->page());
            }
        } catch (\Throwable) {
            session()->flash('error', 'Unable to load comments.');
        }

        return view('admin/comments/index', [
            'comments' => $result['data'],
            'meta' => $result['meta'],
            'search' => $search,
        ]);
    }

    #[Route('GET', '/admin/comments/{id}', [AdminMiddleware::class])]
    public function show(int $id)
    {
        $comment = $this->commentWithContext($id);

        if (!$comment) {
            session()->flash('error', 'Comment not found.');
            return redirect('/admin/comments');
        }

        return view('admin/comments/show', ['comment' => $comment]);
    }

    #[Route('GET', '/admin/comments/{id}/edit', [AdminMiddleware::class])]
    public function edit(int $id)
    {
        $comment = (new CommentTable())->fetchById($id);

        if (!$comment) {
            session()->flash('error', 'Comment not found.');
            return redirect('/admin/comments');
        }

        return view('admin/comments/form', ['comment' => $comment]);
    }

    #[Route('POST', '/admin/comments/{id}', [AdminMiddleware::class])]
    public function update(int $id, Request $request)
    {
        (new CommentTable())->update($id, ['content' => (string) $request->input('content')]);
        session()->flash('success', 'Comment updated successfully.');
        return redirect('/admin/comments');
    }

    #[Route('POST', '/admin/comments/{id}/delete', [AdminMiddleware::class])]
    public function delete(int $id)
    {
        (new CommentTable())->deleteById($id);
        session()->flash('success', 'Comment deleted successfully.');
        return redirect('/admin/comments');
    }

    private function commentWithContext(int $id): mixed
    {
        try {
            $comments = new CommentTable();
            $comments->include(['author', 'post']);
            return $comments->fetchById($id);
        } catch (\Throwable) {
            return null;
        }
    }

    private function filterComments(array $comments, string $search): array
    {
        $needle = strtolower($search);

        return array_values(array_filter($comments, function ($comment) use ($needle) {
            $author = $comment->author->name ?? '';
            $post = $comment->post->title ?? '';

            return str_contains(strtolower((string) $comment->content), $needle)
                || str_contains(strtolower((string) $author), $needle)
                || str_contains(strtolower((string) $post), $needle);
        }));
    }
}
