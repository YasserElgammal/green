<?php

namespace App\Controllers\Web\Admin;

use App\Middleware\AdminMiddleware;
use App\Tables\CommentTable;
use App\Tables\PostTable;
use App\Tables\UserTable;
use YasserElgammal\Green\Http\Request;
use YasserElgammal\Green\Routing\Route;

class CommentController extends BaseAdminController
{
    #[Route('GET', '/admin/comments', [AdminMiddleware::class])]
    public function index()
    {
        $search = $this->query('search');
        $comments = new CommentTable();
        $comments->include(['author', 'post']);
        $query = $comments->query()->latest('id');

        if ($search !== '') {
            $authorIds = array_map(
                static fn ($user) => $user->id,
                (new UserTable())->query()->whereLike('name', "%{$search}%")->fetch()
            );
            $postIds = array_map(
                static fn ($post) => $post->id,
                (new PostTable())->query()->whereLike('title', "%{$search}%")->fetch()
            );

            $query->whereGroup(fn ($query) => $query
                ->whereLike('content', "%{$search}%")
                ->orWhereIn('user_id', $authorIds)
                ->orWhereIn('post_id', $postIds));
        }

        $result = $query->paginate(self::PER_PAGE, $this->page());

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

}
