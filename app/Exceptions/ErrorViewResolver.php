<?php

namespace App\Exceptions;

final class ErrorViewResolver
{
    public function view(int $statusCode): string
    {
        return "errors/{$statusCode}";
    }

    public function hasView(int $statusCode): bool
    {
        return file_exists($this->viewPath($statusCode));
    }

    private function viewPath(int $statusCode): string
    {
        $view = $this->view($statusCode);

        if (!str_ends_with($view, '.twig')) {
            $view .= '.twig';
        }

        return (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2))
            . '/views/' . ltrim($view, '/\\');
    }
}
