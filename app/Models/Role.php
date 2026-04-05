<?php

namespace App\Models;

use YasserElgammal\Green\Database\Model;

/**
 * Role — pure DTO, no database logic.
 */
class Role extends Model
{
    protected string $table      = 'roles';
    protected string $primaryKey = 'id';
}
