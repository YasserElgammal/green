<?php

declare(strict_types=1);

namespace App\Auth;

use ReflectionClass;

final class SessionIdentityKey
{
    /** @param class-string|object $model */
    public static function forModel(string|object $model): string
    {
        $shortName = (new ReflectionClass($model))->getShortName();
        $snakeName = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1_$2', $shortName);
        $snakeName = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', (string) $snakeName);
        $modelName = strtolower((string) $snakeName);

        return "auth.{$modelName}.{$modelName}_id";
    }
}
