<?php

declare(strict_types=1);

namespace App\Services;

final class DomainVerifier
{
    public static function normalizeDomain(string $input): string
    {
        $raw = strtolower(trim($input));
        $raw = preg_replace('#^https?://#', '', $raw) ?? $raw;
        $parts = explode('/', $raw, 2);
        $withoutPath = $parts[0] ?? '';
        $hostPort = explode(':', $withoutPath, 2);
        $withoutPort = $hostPort[0] ?? '';
        return rtrim($withoutPort, '.');
    }

    public static function isDomainFormatValid(string $domain): bool
    {
        return (bool) preg_match('/^(?!-)(?:[a-z0-9-]{1,63}\.)+[a-z]{2,63}$/i', $domain);
    }

    /** @return array{ok: bool, error?: string} */
    public static function verifyByARecord(string $domain): array
    {
        if (config('app')['domain_verify_local_mode'] ?? false) {
            return ['ok' => true];
        }
        $expected = config('app')['domain_verify_vps_ip'] ?? '';
        if ($expected === '') {
            $appUrl = env('APP_URL', '');
            if ($appUrl !== '') {
                $h = parse_url($appUrl, PHP_URL_HOST);
                if (is_string($h) && preg_match('/^\d+\.\d+\.\d+\.\d+$/', $h)) {
                    $expected = $h;
                }
            }
        }
        if ($expected === '') {
            return ['ok' => false, 'error' => 'DOMAIN_VERIFY_VPS_IP is not configured'];
        }
        $records = @dns_get_record($domain, DNS_A);
        if ($records === false || $records === []) {
            return ['ok' => false, 'error' => 'Cannot resolve A record for domain'];
        }
        $ips = [];
        foreach ($records as $r) {
            if (!empty($r['ip'])) {
                $ips[] = $r['ip'];
            }
        }
        if (!in_array($expected, $ips, true)) {
            return ['ok' => false, 'error' => 'A record mismatch. Expected ' . $expected . ', found: ' . implode(', ', $ips)];
        }
        return ['ok' => true];
    }

    /** @return array{ok: bool, error?: string} */
    public static function verifyByTxt(string $domain, string $token): array
    {
        $records = @dns_get_record($domain, DNS_TXT);
        if ($records === false || $records === []) {
            return ['ok' => false, 'error' => 'Cannot resolve TXT record for domain'];
        }
        $flat = [];
        foreach ($records as $r) {
            if (!empty($r['txt'])) {
                $flat[] = trim((string) $r['txt']);
            }
        }
        if (!in_array(trim($token), $flat, true)) {
            return ['ok' => false, 'error' => 'TXT verification token not found'];
        }
        return ['ok' => true];
    }
}
