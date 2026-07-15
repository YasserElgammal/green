<?php

namespace App\Tables;

use App\Models\Like;
use App\Models\User;
use YasserElgammal\Green\Database\Table;
use YasserElgammal\Green\Database\Relations\BelongsTo;

/**
 * LikeTable — Table Gateway for the `likes` table.
 */
class LikeTable extends Table
{
    protected function relations(): array
    {
        return [
            // A like belongs to one user.
            'user' => new BelongsTo(User::class),
        ];
    }

    public function __construct()
    {
        parent::__construct(new Like());
    }
}
