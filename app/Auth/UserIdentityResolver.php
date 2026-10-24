<?php

declare(strict_types=1);

namespace App\Auth;

use App\Tables\UserTable;
use YasserElgammal\Green\AuthJwt\IdentityResolverInterface;

final class UserIdentityResolver implements IdentityResolverInterface
{
    public function resolve(int|string $subject): ?object
    {
        if ((!is_int($subject) && !ctype_digit($subject)) || (int) $subject < 1) {
            return null;
        }
        return (new UserTable())->fetchById($subject);
    }
}
