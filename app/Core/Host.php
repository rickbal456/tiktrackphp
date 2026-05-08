<?php

declare(strict_types=1);

namespace App\Core;

final class Host
{
    public static function current(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $host = strtolower(explode(':', $host, 2)[0]);
        return $host;
    }

    public static function isSaasHost(string $host): bool
    {
        $cfg = config('app');
        $configured = $cfg['saas_base_domains'] ?? [];
        $defaults = ['localhost', '127.0.0.1'];
        $hosts = array_unique(array_merge($configured, $defaults));
        foreach ($hosts as $h) {
            if ($h !== '' && $host === strtolower($h)) {
                return true;
            }
        }
        if (preg_match('/^\d+\.\d+\.\d+\.\d+$/', $host)) {
            return true;
        }
        return false;
    }
}
