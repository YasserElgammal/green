<?php

namespace App\Models;

use YasserElgammal\Green\Database\Model;

/**
 * Post — pure DTO, no database logic.
 */
class Post extends Model
{
    protected string $table      = 'posts';
    protected string $primaryKey = 'id';
}
