<?php

namespace App\Models;

use YasserElgammal\Green\Database\Model;

/**
 * Comment — pure DTO, no database logic.
 */
class Comment extends Model
{
    protected string $table      = 'comments';
    protected string $primaryKey = 'id';
}
