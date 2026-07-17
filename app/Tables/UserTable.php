<?php

namespace App\Tables;

use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use YasserElgammal\Green\Database\Table;
use YasserElgammal\Green\Database\Relations\HasMany;
use YasserElgammal\Green\Database\Relations\ManyToMany;

/**
 * UserTable — Table Gateway for the `users` table.
 *
 * All relations are declared here, in the Table layer.
 * Models stay clean pure DTOs.
 */
class UserTable extends Table
{
    protected function relations(): array
    {
        return [
            // A user has many posts.
            'posts' => new HasMany(Post::class),

            // A user has many roles through the `user_roles` pivot table.
            'roles' => new ManyToMany(Role::class, pivot: 'user_roles'),
        ];
    }

    public function __construct()
    {
        parent::__construct(new User());
    }
}
