<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $file = base_path('app/views/' . str_replace('.', '/', $view) . '.php');
        if (!is_readable($file)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($view);
            return;
        }
        require $file;
    }

    public static function renderLayout(string $layout, string $view, array $data = []): void
    {
        $data['__innerView'] = $view;
        self::render('layouts.' . $layout, $data);
    }

    public static function dashboard(string $view, array $data = []): void
    {
        $data['user'] = \App\Core\Auth::user();
        self::renderLayout('dashboard', $view, $data);
    }

    public static function authPage(string $view, array $data = []): void
    {
        self::renderLayout('auth', $view, $data);
    }

    public static function json(mixed $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public static function redirect(string $to, int $code = 302): never
    {
        header('Location: ' . $to, true, $code);
        exit;
    }
}
