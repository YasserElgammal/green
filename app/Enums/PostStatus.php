<?php

namespace App\Enums;

use App\Traits\EnumHelpers;

enum PostStatus: string
{
    use EnumHelpers;

    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public static function default(): self
    {
        return self::Draft;
    }
}
