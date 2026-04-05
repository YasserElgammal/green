<?php

namespace App\Tables;

use App\Models\Role;
use App\Models\User;
use YasserElgammal\Green\Database\Table;

/**
 * RoleTable — Table Gateway for the `roles` table.
 */
class RoleTable extends Table
{
    protected array $relations = [

        // ── manyToMany ───────────────────────────────────────────────────────
        // A role belongs to many users through the `user_roles` pivot table.
        // user_roles.role_id → roles.id
        // user_roles.user_id → users.id
        'users' => [
            'type'        => 'manyToMany',
            'model'       => User::class,
            'pivot'       => 'user_roles',
            'foreign_key' => 'role_id',
            'related_key' => 'user_id',
            'local_key'   => 'id',
        ],
    ];

    public function __construct()
    {
        parent::__construct(new Role());
    }
}
