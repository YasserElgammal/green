<?php

namespace App\Tables;

use App\Models\Role;
use App\Models\User;
use YasserElgammal\Green\Database\Table;
use YasserElgammal\Green\Database\Relations\ManyToMany;

/**
 * RoleTable — Table Gateway for the `roles` table.
 */
class RoleTable extends Table
{
    protected function relations(): array
    {
        return [
            // A role belongs to many users through the `user_roles` pivot table.
            'users' => new ManyToMany(User::class, pivot: 'user_roles'),
        ];
    }

    public function __construct()
    {
        parent::__construct(new Role());
    }
}
