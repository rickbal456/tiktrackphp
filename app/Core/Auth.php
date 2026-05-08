<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /** @return array{id: string, name: string, email: string, role: string}|null */
    public static function user(): ?array
    {
        $u = $_SESSION['user'] ?? null;
        return is_array($u) ? $u : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** @param array{id: string, name: string, email: string, role: string} $user */
    public static function login(array $user): void
    {
        $_SESSION['user'] = $user;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            View::redirect('/login');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (self::user()['role'] !== 'admin') {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        return $u !== null && ($u['role'] ?? '') === 'admin';
    }

    public static function flash(string $key, string $message): void
    {
        $_SESSION['_flash'][$key] = $message;
    }

    public static function getFlash(string $key): ?string
    {
        $m = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return is_string($m) ? $m : null;
    }
}
