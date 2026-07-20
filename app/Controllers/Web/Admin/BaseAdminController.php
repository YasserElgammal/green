<?php

namespace App\Controllers\Web\Admin;

abstract class BaseAdminController
{
    protected const PER_PAGE = 10;

    protected function query(string $key): string
    {
        return trim((string) ($_GET[$key] ?? ''));
    }

    protected function sort(array $allowed, string $default): string
    {
        $sort = (string) ($_GET['sort'] ?? $default);
        return in_array($sort, $allowed, true) ? $sort : $default;
    }

    protected function direction(): string
    {
        $direction = strtoupper((string) ($_GET['direction'] ?? 'DESC'));
        return in_array($direction, ['ASC', 'DESC'], true) ? $direction : 'DESC';
    }

    protected function page(): int
    {
        return max(1, (int) ($_GET['page'] ?? 1));
    }

}
