<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class TikTokService
{
    private const API_URL = 'https://business-api.tiktok.com/open_api/v1.3/event/track/';

    public static function normalizeTikTokClickId(string $value): string
    {
        return trim($value);
    }

    public static function isTikTokClickIdValid(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }
        $n = self::normalizeTikTokClickId($value);
        if ($n === '') {
            return false;
        }
        if (preg_match('/clickid/i', $n)) {
            return false;
        }
        return true;
    }

    public static function ensureTtclidInUrl(string $url, string $ttclid): string
    {
        try {
            $p = parse_url($url);
            if ($p === false || !isset($p['scheme'], $p['host'])) {
                return $url;
            }
            $q = [];
            if (!empty($p['query'])) {
                parse_str($p['query'], $q);
            }
            if (empty($q['ttclid'])) {
                $q['ttclid'] = $ttclid;
            }
            $scheme = $p['scheme'];
            $host = $p['host'];
            $port = isset($p['port']) ? ':' . $p['port'] : '';
            $path = $p['path'] ?? '/';
            $qs = http_build_query($q);
            $frag = isset($p['fragment']) ? '#' . rawurlencode($p['fragment']) : '';
            $base = "{$scheme}://{$host}{$port}{$path}";
            return $base . ($qs !== '' ? '?' . $qs : '') . $frag;
        } catch (\Throwable) {
            return $url;
        }
    }

    public static function sha256Hash(string $value): string
    {
        return hash('sha256', $value);
    }

    public static function hashEmail(string $email): string
    {
        return self::sha256Hash(strtolower(trim($email)));
    }

    public static function hashPhone(string $phone): string
    {
        $cleaned = preg_replace('/[^+\d]/', '', $phone) ?? '';
        return self::sha256Hash($cleaned);
    }

    public static function hashExternalId(string $id): string
    {
        return self::sha256Hash(trim($id));
    }

    /**
     * @param array{accessToken: string, pixelId: string} $config
     * @param array{eventName: string, eventTime: \DateTimeInterface, eventId?: string|null, user: array, page: array, properties?: array} $eventData
     * @return array{success: bool, response: mixed}
     */
    public static function sendEvent(array $config, array $eventData): array
    {
        $user = [];
        $u = $eventData['user'];
        if (isset($u['ttclid']) && self::isTikTokClickIdValid((string) $u['ttclid'])) {
            $user['ttclid'] = self::normalizeTikTokClickId((string) $u['ttclid']);
        }
        if (!empty($u['email'])) {
            $user['email'] = self::hashEmail((string) $u['email']);
        }
        if (!empty($u['phone'])) {
            $user['phone'] = self::hashPhone((string) $u['phone']);
        }
        if (!empty($u['externalId'])) {
            $user['external_id'] = self::hashExternalId((string) $u['externalId']);
        }
        if (!empty($u['ip'])) {
            $user['ip'] = (string) $u['ip'];
        }
        if (!empty($u['userAgent'])) {
            $user['user_agent'] = (string) $u['userAgent'];
        }

        $properties = [];
        $pr = $eventData['properties'] ?? [];
        if (!empty($pr['currency'])) {
            $properties['currency'] = $pr['currency'];
        }
        if (isset($pr['value']) && $pr['value'] !== null && $pr['value'] !== '') {
            $properties['value'] = (float) $pr['value'];
        }
        if (!empty($pr['contentType'])) {
            $properties['content_type'] = $pr['contentType'];
        }
        if (!empty($pr['contents'])) {
            $properties['contents'] = $pr['contents'];
        }

        $event = [
            'event' => $eventData['eventName'],
            'event_time' => (int) floor($eventData['eventTime']->getTimestamp()),
            'user' => $user,
            'page' => $eventData['page'],
        ];
        if (!empty($eventData['eventId'])) {
            $event['event_id'] = (string) $eventData['eventId'];
        }
        if ($properties !== []) {
            $event['properties'] = $properties;
        }

        $payload = [
            'event_source' => 'web',
            'event_source_id' => $config['pixelId'],
            'data' => [$event],
        ];

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Access-Token: ' . $config['accessToken'],
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
        ]);
        $body = curl_exec($ch);
        $ok = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $responseData = json_decode((string) $body, true);
        if (!is_array($responseData)) {
            $responseData = ['raw' => $body];
        }
        $success = $ok >= 200 && $ok < 300 && (($responseData['code'] ?? -1) === 0);
        return ['success' => $success, 'response' => $responseData];
    }

    /**
     * Create conversion row pending, send, update status (like fireAndRecordEvent).
     * @param array{id: string, accessToken: string, pixelId: string} $config
     */
    public static function fireAndRecordEvent(
        array $config,
        string $userId,
        array $eventData,
        string $pageUrl
    ): void {
        $pdo = Database::pdo();
        $u = $eventData['user'];
        $id = Database::uuid();
        $stmt = $pdo->prepare(
            'INSERT INTO conversions (id, user_id, config_id, event_name, event_time, event_id, ttclid, ip, user_agent, url, status)
             VALUES (?,?,?,?,?,?,?,?,?,?,\'pending\')'
        );
        $stmt->execute([
            $id,
            $userId,
            $config['id'],
            $eventData['eventName'],
            $eventData['eventTime']->format('Y-m-d H:i:s'),
            $eventData['eventId'] ?? null,
            $u['ttclid'] ?? null,
            $u['ip'] ?? null,
            $u['userAgent'] ?? null,
            $pageUrl,
        ]);
        try {
            $r = self::sendEvent(
                ['accessToken' => $config['accessToken'], 'pixelId' => $config['pixelId']],
                $eventData
            );
            $upd = $pdo->prepare('UPDATE conversions SET status = ?, response = ? WHERE id = ?');
            $upd->execute([
                $r['success'] ? 'sent' : 'failed',
                json_encode($r['response'], JSON_THROW_ON_ERROR),
                $id,
            ]);
        } catch (\Throwable $e) {
            $upd = $pdo->prepare('UPDATE conversions SET status = ?, response = ? WHERE id = ?');
            $upd->execute(['failed', json_encode(['error' => $e->getMessage()], JSON_THROW_ON_ERROR), $id]);
        }
    }

    public static function isAbsoluteHttpUrl(string $value): bool
    {
        $p = parse_url($value);
        if ($p === false || empty($p['scheme'])) {
            return false;
        }
        return in_array($p['scheme'], ['http', 'https'], true);
    }
}
