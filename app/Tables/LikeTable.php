<?php

namespace App\Tables;

use App\Models\Like;
use App\Models\User;
use YasserElgammal\Green\Database\Table;

/**
 * LikeTable — Table Gateway for the `likes` table.
 */
class LikeTable extends Table
{
    protected array $relations = [

        // ── belongsTo ────────────────────────────────────────────────────────
        // A like belongs to one user.
        // likes.user_id → users.id
        'user' => [
            'type'        => 'belongsTo',
            'model'       => User::class,
            'foreign_key' => 'user_id',
            'owner_key'   => 'id',
        ],
    ];

    public function __construct()
    {
        parent::__construct(new Like());
    }
}
