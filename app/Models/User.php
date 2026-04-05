<?php

namespace App\Models;

use YasserElgammal\Green\Database\Model;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
}
