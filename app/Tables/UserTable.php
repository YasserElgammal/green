<?php

namespace App\Tables;

use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use YasserElgammal\Green\Database\Table;

/**
 * UserTable — Table Gateway for the `users` table.
 *
 * All relations are declared here, in the Table layer.
 * Models stay clean pure DTOs.
 */
class UserTable extends Table
{
    protected array $relations = [

        // ── hasMany ──────────────────────────────────────────────────────────
        // A user has many posts.
        // posts.user_id → users.id
        'posts' => [
            'type'        => 'hasMany',
            'model'       => Post::class,
            'foreign_key' => 'user_id',
            'local_key'   => 'id',
        ],

        // ── manyToMany ───────────────────────────────────────────────────────
        // A user has many roles through the `user_roles` pivot table.
        // user_roles.user_id  → users.id
        // user_roles.role_id  → roles.id
        'roles' => [
            'type'        => 'manyToMany',
            'model'       => Role::class,
            'pivot'       => 'user_roles',
            'foreign_key' => 'user_id',
            'related_key' => 'role_id',
            'local_key'   => 'id',
        ],
    ];

    public function __construct()
    {
        parent::__construct(new User());
    }
}
