<?php

namespace App\Controllers\Web\Admin;

abstract class BaseAdminController
{
    protected const PER_PAGE = 10;

    protected function paginateQuery(object $query, int $perPage, int $page): array
    {
        $countQuery = clone $query;
        $totalItems = (int) $countQuery->select('COUNT(*)')->executeQuery()->fetchOne();
        $totalPages = (int) ceil($totalItems / $perPage);
        $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));

        $data = $query
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->executeQuery()
            ->fetchAllAssociative();

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1,
            ],
        ];
    }

    protected function paginateArray(array $items, int $perPage, int $page): array
    {
        $totalItems = count($items);
        $totalPages = (int) ceil($totalItems / $perPage);
        $page = max(1, min($page, $totalPages > 0 ? $totalPages : 1));

        return [
            'data' => array_slice($items, ($page - 1) * $perPage, $perPage),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1,
            ],
        ];
    }

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
