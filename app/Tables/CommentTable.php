<?php

namespace App\Tables;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use YasserElgammal\Green\Database\Table;
use YasserElgammal\Green\Database\Relations\BelongsTo;
use YasserElgammal\Green\Database\Relations\HasMany;

/**
 * CommentTable — Table Gateway for the `comments` table.
 */
class CommentTable extends Table
{
    protected function relations(): array
    {
        return [
            // A comment has many likes.
            'likes' => new HasMany(Like::class),

            // A comment belongs to one user (author).
            'author' => new BelongsTo(User::class),

            // A comment belongs to one post.
            'post' => new BelongsTo(Post::class),
        ];
    }

    public function __construct()
    {
        parent::__construct(new Comment());
    }
}
