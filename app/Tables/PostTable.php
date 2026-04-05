<?php

namespace App\Tables;

use App\Models\Comment;
use App\Models\User;
use App\Models\Post;
use YasserElgammal\Green\Database\Table;

/**
 * PostTable — Table Gateway for the `posts` table.
 */
class PostTable extends Table
{
    protected array $relations = [

        // ── belongsTo ────────────────────────────────────────────────────────
        // A post belongs to one user.
        // posts.user_id → users.id
        'author' => [
            'type'        => 'belongsTo',
            'model'       => User::class,
            'foreign_key' => 'user_id',   // column ON the post
            'owner_key'   => 'id',        // column ON the user
        ],

        // ── hasMany ──────────────────────────────────────────────────────────
        // A post has many comments.
        // comments.post_id → posts.id
        'comments' => [
            'type'        => 'hasMany',
            'model'       => Comment::class,
            'foreign_key' => 'post_id',
            'local_key'   => 'id',
        ],
    ];

    public function __construct()
    {
        parent::__construct(new Post());
    }
}
