<?php

namespace App\Tables;

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
    public function __construct()
    {
        parent::__construct(new User());
    }
}
