<?php

declare(strict_types=1);

namespace App\Services;

final class DomainVerifier
{
    /**
     * Cloudflare published IPv4 ranges (https://www.cloudflare.com/ips-v4).
     * When a domain uses Cloudflare proxy (orange cloud), its public A record
     * resolves to one of these instead of the origin IP.
     *
     * @var list<string>
     */
    private const CLOUDFLARE_V4 = [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
    ];

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
        if (in_array($expected, $ips, true)) {
            return ['ok' => true];
        }
        // Cloudflare proxy ON: A record hides the origin IP behind Cloudflare's
        // anycast IPs. Accept the domain when every resolved IP is Cloudflare's.
        if ($ips !== [] && self::allCloudflareIps($ips)) {
            return ['ok' => true];
        }
        return ['ok' => false, 'error' => 'A record mismatch. Expected ' . $expected . ', found: ' . implode(', ', $ips)];
    }

    /**
     * @param list<string> $ips
     */
    private static function allCloudflareIps(array $ips): bool
    {
        foreach ($ips as $ip) {
            if (!self::isCloudflareIp($ip)) {
                return false;
            }
        }
        return true;
    }

    private static function isCloudflareIp(string $ip): bool
    {
        $long = ip2long($ip);
        if ($long === false) {
            return false;
        }
        foreach (self::CLOUDFLARE_V4 as $cidr) {
            [$subnet, $bits] = explode('/', $cidr, 2);
            $subnetLong = ip2long($subnet);
            if ($subnetLong === false) {
                continue;
            }
            $mask = -1 << (32 - (int) $bits);
            if (($long & $mask) === ($subnetLong & $mask)) {
                return true;
            }
        }
        return false;
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
