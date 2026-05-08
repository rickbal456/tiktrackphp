<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Host;
use App\Services\TikTokService;
use DateTimeImmutable;
use Throwable;

final class LpController
{
    public function show(string $slug): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT lp.*, tc.access_token, tc.pixel_id, tc.id AS config_db_id FROM landing_pages lp
             LEFT JOIN tiktok_configs tc ON tc.id = lp.config_id
             WHERE lp.slug = ? LIMIT 1'
        );
        $stmt->execute([$slug]);
        $lp = $stmt->fetch();
        if (!$lp || !(int) $lp['is_active']) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        $stmt = $pdo->prepare(
            'SELECT lpd.*, cd.domain, cd.status AS domain_status FROM landing_page_domains lpd
             JOIN custom_domains cd ON cd.id = lpd.custom_domain_id
             WHERE lpd.landing_page_id = ?'
        );
        $stmt->execute([$lp['id']]);
        $links = $stmt->fetchAll();
        $verifiedDomains = [];
        foreach ($links as $link) {
            if (($link['domain_status'] ?? '') === 'verified') {
                $verifiedDomains[] = strtolower((string) $link['domain']);
            }
        }

        $currentHost = Host::current();
        if ($verifiedDomains !== []) {
            if (Host::isSaasHost($currentHost)) {
                http_response_code(404);
                echo 'Not Found';
                return;
            }
            if (!in_array($currentHost, $verifiedDomains, true)) {
                http_response_code(404);
                echo 'Not Found';
                return;
            }
        }

        $clickParam = $lp['click_id_param'] ?: 'ttclid';
        $ttclid = isset($_GET[$clickParam]) ? (is_array($_GET[$clickParam]) ? '' : (string) $_GET[$clickParam]) : '';

        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        $ip = $forwarded !== '' ? trim(explode(',', $forwarded)[0]) : (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $userAgent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'https';
        $hostHeader = $_SERVER['HTTP_HOST'] ?? '';
        $pageUrl = $proto . '://' . $hostHeader . '/lp/' . rawurlencode($slug) . ($ttclid !== '' ? '?' . rawurlencode($clickParam) . '=' . rawurlencode($ttclid) : '');

        $eventId = Database::uuid();
        if (!empty($lp['config_db_id']) && $ttclid !== '' && TikTokService::isTikTokClickIdValid($ttclid)) {
            try {
                TikTokService::fireAndRecordEvent(
                    [
                        'id' => (string) $lp['config_db_id'],
                        'accessToken' => (string) $lp['access_token'],
                        'pixelId' => (string) $lp['pixel_id'],
                    ],
                    (string) $lp['user_id'],
                    [
                        'eventName' => (string) $lp['event_name'],
                        'eventId' => $eventId,
                        'eventTime' => new DateTimeImmutable(),
                        'user' => ['ttclid' => $ttclid, 'ip' => $ip, 'userAgent' => $userAgent],
                        'page' => ['url' => $pageUrl],
                    ],
                    $pageUrl
                );
            } catch (Throwable) {
                // ignore
            }
        }

        $stmt = $pdo->prepare(
            'SELECT name, urls, delay_ms, sort_order FROM landing_page_sections
             WHERE landing_page_id = ? AND is_active = 1 ORDER BY sort_order ASC'
        );
        $stmt->execute([$lp['id']]);
        $sections = [];
        foreach ($stmt->fetchAll() as $row) {
            $urls = json_decode((string) $row['urls'], true);
            if (!is_array($urls)) {
                $urls = [];
            }
            $sections[] = [
                'name' => (string) $row['name'],
                'urls' => $urls,
                'delayMs' => (int) $row['delay_ms'],
                'sortOrder' => (int) $row['sort_order'],
            ];
        }

        $pageTitle = (string) $lp['page_title'];
        $pixelId = (string) ($lp['pixel_id'] ?? '');
        $eventName = (string) $lp['event_name'];
        header('Content-Type: text/html; charset=utf-8');
        require base_path('app/views/lp/show.php');
    }
}
