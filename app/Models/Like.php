<?php

namespace App\Models;

use YasserElgammal\Green\Database\Model;

/**
 * Like — pure DTO, no database logic.
 */
class Like extends Model
{
    protected string $table      = 'likes';
    protected string $primaryKey = 'id';
}
