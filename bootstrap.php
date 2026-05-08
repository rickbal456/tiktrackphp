<?php

declare(strict_types=1);

$basePath = __DIR__;

if (file_exists($basePath . '/vendor/autoload.php')) {
    require $basePath . '/vendor/autoload.php';
}

/** @var array<string, string> */
$env = [];
$envFile = $basePath . '/.env';
if (is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if ($v !== '' && ($v[0] === '"' || $v[0] === "'")) {
            $v = trim($v, "\"'");
        }
        $env[$k] = $v;
        $_ENV[$k] = $v;
        putenv("$k=$v");
    }
}

function env(string $key, ?string $default = null): ?string
{
    $v = $_ENV[$key] ?? getenv($key);
    if ($v === false || $v === null || $v === '') {
        return $default;
    }
    return (string) $v;
}

function base_path(string $path = ''): string
{
    global $basePath;
    return $path === '' ? $basePath : $basePath . '/' . ltrim($path, '/');
}

function config(string $file): array
{
    $p = base_path('config/' . $file . '.php');
    if (!is_readable($p)) {
        return [];
    }
    /** @var array */
    return require $p;
}
