<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = [], ?string $layout = 'app'): string
    {
        $data['currentUser'] = Auth::user();
        $data['flashMessages'] = Flash::messages();
        $data['currentPath'] = Request::capture()->path();

        $viewFile = self::path($view);
        if (!is_file($viewFile)) {
            throw new \RuntimeException('Vista no encontrada: ' . $view);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();

        if ($layout === null) {
            return $content;
        }

        $layoutFile = self::path('layouts/' . $layout);
        if (!is_file($layoutFile)) {
            throw new \RuntimeException('Layout no encontrado: ' . $layout);
        }

        ob_start();
        require $layoutFile;
        return (string) ob_get_clean();
    }

    /** @param array<string, mixed> $data */
    public static function partial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require self::path($view);
    }

    public static function exists(string $view): bool
    {
        return is_file(self::path($view));
    }

    private static function path(string $view): string
    {
        return BASE_PATH . '/app/Views/' . str_replace('.', '/', $view) . '.php';
    }
}
