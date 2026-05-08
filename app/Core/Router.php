<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, array{0: class-string, 1: string}> */
    private array $routes = [];

    /**
     * @param callable(): void $fallback
     */
    public function __construct(
        private readonly \Closure $fallback
    ) {
    }

    /**
     * @param class-string $controller
     */
    public function add(string $method, string $path, string $controller, string $action): void
    {
        $key = strtoupper($method) . ' ' . $this->normalizePath($path);
        $this->routes[$key] = [$controller, $action];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = $this->normalizePath($path);
        $key = strtoupper($method) . ' ' . $path;

        if (isset($this->routes[$key])) {
            [$class, $action] = $this->routes[$key];
            $controller = new $class();
            $controller->{$action}();
            return;
        }

        ($this->fallback)();
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }
}
