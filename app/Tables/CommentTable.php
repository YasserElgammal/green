<?php

namespace App\Tables;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use YasserElgammal\Green\Database\Table;

/**
 * CommentTable — Table Gateway for the `comments` table.
 */
class CommentTable extends Table
{
    protected array $relations = [

        // ── hasMany ──────────────────────────────────────────────────────────
        // A comment has many likes.
        // likes.comment_id → comments.id
        'likes' => [
            'type'        => 'hasMany',
            'model'       => Like::class,
            'foreign_key' => 'comment_id',
            'local_key'   => 'id',
        ],

        // ── belongsTo ────────────────────────────────────────────────────────
        // A comment belongs to one user (author).
        // comments.user_id → users.id
        'author' => [
            'type'        => 'belongsTo',
            'model'       => User::class,
            'foreign_key' => 'user_id',
            'owner_key'   => 'id',
        ],

        'post' => [
            'type'        => 'belongsTo',
            'model'       => Post::class,
            'foreign_key' => 'post_id',
            'owner_key'   => 'id',
        ],
    ];

    public function __construct()
    {
        parent::__construct(new Comment());
    }
}
