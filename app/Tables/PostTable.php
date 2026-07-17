<?php

namespace App\Tables;

use App\Models\Comment;
use App\Models\User;
use App\Models\Post;
use YasserElgammal\Green\Database\Table;
use YasserElgammal\Green\Database\Relations\BelongsTo;
use YasserElgammal\Green\Database\Relations\HasMany;

/**
 * PostTable — Table Gateway for the `posts` table.
 */
class PostTable extends Table
{
    protected function relations(): array
    {
        return [
            // A post belongs to one user.
            'author' => new BelongsTo(User::class),

            // A post has many comments.
            'comments' => new HasMany(Comment::class),
        ];
    }

    public function __construct()
    {
        parent::__construct(new Post());
    }
}
