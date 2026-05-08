<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;
use App\Services\TikTokService;
use DateTimeImmutable;
use PDO;

final class ConversionController
{
    public function index(): void
    {
        Auth::requireAuth();
        $pdo = Database::pdo();
        $u = Auth::user();
        $isAdmin = $u['role'] === 'admin';
        $detailId = $_GET['detail'] ?? null;

        $sql = 'SELECT c.*, tc.pixel_id, tc.event_name AS config_event, u.name AS user_name FROM conversions c
            JOIN tiktok_configs tc ON tc.id = c.config_id
            JOIN users u ON u.id = c.user_id';
        $sql .= $isAdmin ? ' ORDER BY c.created_at DESC LIMIT 100' : ' WHERE c.user_id = ? ORDER BY c.created_at DESC LIMIT 100';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($isAdmin ? [] : [$u['id']]);
        $conversions = $stmt->fetchAll();

        $cfgs = $pdo->prepare($isAdmin ? 'SELECT * FROM tiktok_configs WHERE is_active = 1 ORDER BY created_at DESC' : 'SELECT * FROM tiktok_configs WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC');
        $cfgs->execute($isAdmin ? [] : [$u['id']]);
        $configs = $cfgs->fetchAll();

        $lps = $pdo->prepare($isAdmin
            ? 'SELECT lp.* FROM landing_pages lp WHERE lp.is_active = 1 ORDER BY lp.created_at DESC'
            : 'SELECT lp.* FROM landing_pages lp WHERE lp.user_id = ? AND lp.is_active = 1 ORDER BY lp.created_at DESC');
        $lps->execute($isAdmin ? [] : [$u['id']]);
        $landingPages = $lps->fetchAll();
        foreach ($landingPages as &$lp) {
            $s = $pdo->prepare(
                'SELECT lpd.is_primary, cd.domain, cd.status FROM landing_page_domains lpd
                 JOIN custom_domains cd ON cd.id = lpd.custom_domain_id
                 WHERE lpd.landing_page_id = ? ORDER BY lpd.is_primary DESC, lpd.created_at ASC'
            );
            $s->execute([$lp['id']]);
            $lp['domain_links'] = $s->fetchAll();
        }
        unset($lp);

        $detail = null;
        $detailPreview = null;
        if ($detailId) {
            $stmt = $pdo->prepare('SELECT c.*, tc.pixel_id, tc.access_token, tc.event_name AS config_event FROM conversions c JOIN tiktok_configs tc ON tc.id = c.config_id WHERE c.id = ?');
            $stmt->execute([$detailId]);
            $detail = $stmt->fetch();
            if ($detail && !$isAdmin && $detail['user_id'] !== $u['id']) {
                $detail = null;
            }
            if ($detail) {
                $detailPreview = self::buildPreviewPayload($detail);
            }
        }

        View::dashboard('conversions.index', [
            'title' => 'Conversions',
            'conversions' => $conversions,
            'configs' => $configs,
            'landingPages' => $landingPages,
            'isAdmin' => $isAdmin,
            'detail' => $detail,
            'detailPreview' => $detailPreview,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Auth::flash('error', 'Invalid CSRF');
            View::redirect('/conversions');
        }
        $configId = (string) ($_POST['config_id'] ?? '');
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM tiktok_configs WHERE id = ?');
        $stmt->execute([$configId]);
        $cfg = $stmt->fetch();
        if (!$cfg || ($cfg['user_id'] !== Auth::user()['id'] && Auth::user()['role'] !== 'admin')) {
            Auth::flash('error', 'Invalid config');
            View::redirect('/conversions');
        }

        $eventName = trim((string) ($_POST['event_name'] ?? 'Purchase'));
        $eventTime = self::parseEventTime($_POST['event_time'] ?? null) ?? new DateTimeImmutable();
        $ttclid = trim((string) ($_POST['ttclid'] ?? ''));
        if (!TikTokService::isTikTokClickIdValid($ttclid)) {
            Auth::flash('error', 'ttclid is required (placeholder not allowed)');
            View::redirect('/conversions');
        }
        $url = trim((string) ($_POST['url'] ?? ''));
        if (!TikTokService::isAbsoluteHttpUrl($url)) {
            Auth::flash('error', 'Page URL must be absolute http(s)');
            View::redirect('/conversions');
        }
        $purchase = in_array($eventName, ['Purchase', 'CompletePayment', 'PlaceAnOrder'], true);
        $value = $_POST['value'] ?? '';
        $currency = strtoupper(trim((string) ($_POST['currency'] ?? '')));
        if ($purchase) {
            $v = is_numeric($value) ? (float) $value : 0.0;
            if ($v <= 0) {
                Auth::flash('error', 'Value required for purchase events');
                View::redirect('/conversions');
            }
            if ($currency === '') {
                Auth::flash('error', 'Currency required');
                View::redirect('/conversions');
            }
        } else {
            $v = ($value !== '' && is_numeric($value)) ? (float) $value : null;
        }

        $eventId = trim((string) ($_POST['event_id'] ?? ''));
        if ($eventId === '') {
            $eventId = 'manual_' . Database::uuid();
        }

        $id = Database::uuid();
        $pdo->prepare(
            'INSERT INTO conversions (id, user_id, config_id, event_name, event_time, event_id, ttclid, email, phone, external_id, ip, user_agent, url, referrer, currency, value, content_type, contents, status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,\'pending\')'
        )->execute([
            $id,
            Auth::user()['id'],
            $configId,
            $eventName,
            $eventTime->format('Y-m-d H:i:s.u'),
            $eventId,
            TikTokService::normalizeTikTokClickId($ttclid),
            $_POST['email'] ?: null,
            $_POST['phone'] ?: null,
            $_POST['external_id'] ?: null,
            $_POST['ip'] ?: null,
            $_POST['user_agent'] ?: null,
            $url,
            $_POST['referrer'] ?: null,
            $currency ?: null,
            $v,
            $_POST['content_type'] ?: 'product',
            null,
        ]);
        Auth::flash('success', 'Conversion created');
        View::redirect('/conversions');
    }

    public function importCsv(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Auth::flash('error', 'Invalid CSRF');
            View::redirect('/conversions');
        }
        $configId = (string) ($_POST['config_id'] ?? '');
        if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            Auth::flash('error', 'CSV file required');
            View::redirect('/conversions');
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM tiktok_configs WHERE id = ?');
        $stmt->execute([$configId]);
        $cfg = $stmt->fetch();
        if (!$cfg || ($cfg['user_id'] !== Auth::user()['id'] && Auth::user()['role'] !== 'admin')) {
            Auth::flash('error', 'Invalid config');
            View::redirect('/conversions');
        }

        $fh = fopen($_FILES['csv']['tmp_name'], 'r');
        if ($fh === false) {
            Auth::flash('error', 'Cannot read CSV');
            View::redirect('/conversions');
        }
        $header = fgetcsv($fh);
        if ($header === false) {
            fclose($fh);
            Auth::flash('error', 'Empty CSV');
            View::redirect('/conversions');
        }
        $map = [];
        foreach ($header as $i => $h) {
            $map[strtolower(trim((string) $h))] = $i;
        }
        $count = 0;
        while (($row = fgetcsv($fh)) !== false) {
            $g = static function (array $row, array $map, string ...$keys) use ($header): ?string {
                foreach ($keys as $k) {
                    $lk = strtolower($k);
                    if (isset($map[$lk])) {
                        $v = $row[$map[$lk]] ?? '';
                        return trim((string) $v) !== '' ? trim((string) $v) : null;
                    }
                }
                return null;
            };
            $eventName = $g($row, $map, 'eventname', 'event_name') ?? 'Purchase';
            $eventTimeRaw = $g($row, $map, 'eventtime', 'event_time');
            $eventTime = self::parseEventTime($eventTimeRaw) ?? new DateTimeImmutable();
            $ttclid = $g($row, $map, 'ttclid') ?? '';
            if (!TikTokService::isTikTokClickIdValid($ttclid)) {
                continue;
            }
            $url = $g($row, $map, 'url') ?? '';
            if (!TikTokService::isAbsoluteHttpUrl($url)) {
                continue;
            }
            $purchase = in_array($eventName, ['Purchase', 'CompletePayment', 'PlaceAnOrder'], true);
            $valRaw = $g($row, $map, 'value');
            $cur = strtoupper((string) ($g($row, $map, 'currency') ?? ''));
            if ($purchase) {
                $v = is_numeric($valRaw) ? (float) $valRaw : 0;
                if ($v <= 0 || $cur === '') {
                    continue;
                }
            } else {
                $v = ($valRaw !== null && is_numeric($valRaw)) ? (float) $valRaw : null;
            }
            $eventId = $g($row, $map, 'eventid', 'event_id') ?? ('csv_' . Database::uuid());
            $id = Database::uuid();
            $pdo->prepare(
                'INSERT INTO conversions (id, user_id, config_id, event_name, event_time, event_id, ttclid, email, phone, external_id, ip, user_agent, url, referrer, currency, value, content_type, status)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,\'pending\')'
            )->execute([
                $id,
                Auth::user()['id'],
                $configId,
                $eventName,
                $eventTime->format('Y-m-d H:i:s.u'),
                $eventId,
                TikTokService::normalizeTikTokClickId($ttclid),
                $g($row, $map, 'email'),
                $g($row, $map, 'phone'),
                $g($row, $map, 'externalid', 'external_id'),
                $g($row, $map, 'ip'),
                $g($row, $map, 'useragent', 'user_agent'),
                $url,
                $g($row, $map, 'referrer'),
                $cur ?: null,
                $v,
                $g($row, $map, 'contenttype', 'content_type') ?: 'product',
            ]);
            $count++;
        }
        fclose($fh);
        Auth::flash('success', "$count conversion(s) imported");
        View::redirect('/conversions');
    }

    public function sendBulk(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Auth::flash('error', 'Invalid CSRF');
            View::redirect('/conversions');
        }
        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids) || $ids === []) {
            Auth::flash('error', 'Select conversions');
            View::redirect('/conversions');
        }
        $this->sendIds(array_map('strval', $ids));
    }

    public function sendAllPending(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Auth::flash('error', 'Invalid CSRF');
            View::redirect('/conversions');
        }
        $pdo = Database::pdo();
        $u = Auth::user();
        $isAdmin = $u['role'] === 'admin';
        $sql = "SELECT id FROM conversions WHERE status = 'pending'";
        $sql .= $isAdmin ? '' : ' AND user_id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($isAdmin ? [] : [$u['id']]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $this->sendIds(array_map('strval', $ids));
    }

    /** @param list<string> $ids */
    private function sendIds(array $ids): void
    {
        if ($ids === []) {
            Auth::flash('error', 'Nothing to send');
            View::redirect('/conversions');
        }
        $r = $this->processSendIds($ids);
        if (!empty($r['notFound'])) {
            Auth::flash('error', 'No conversions found');
        } else {
            Auth::flash('success', 'Sent: ' . $r['sent'] . ', Failed: ' . $r['failed']);
        }
        View::redirect('/conversions');
    }

    /**
     * @param list<string> $ids
     * @return array{sent: int, failed: int, results: list<array{id: string, status: string}>, notFound?: bool}
     */
    private function processSendIds(array $ids): array
    {
        $pdo = Database::pdo();
        $u = Auth::user();
        $isAdmin = $u['role'] === 'admin';
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT c.*, tc.access_token, tc.pixel_id FROM conversions c JOIN tiktok_configs tc ON tc.id = c.config_id WHERE c.id IN ($placeholders)";
        $sql .= $isAdmin ? '' : ' AND c.user_id = ?';
        $params = $ids;
        if (!$isAdmin) {
            $params[] = $u['id'];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        if ($rows === []) {
            return ['sent' => 0, 'failed' => 0, 'results' => [], 'notFound' => true];
        }

        $grouped = [];
        foreach ($rows as $conv) {
            $grouped[$conv['config_id']][] = $conv;
        }

        $sent = 0;
        $failed = 0;
        $results = [];
        foreach ($grouped as $configId => $convs) {
            $config = ['accessToken' => $convs[0]['access_token'], 'pixelId' => $convs[0]['pixel_id']];
            $prepared = [];
            foreach ($convs as $conv) {
                $ttclidRaw = (string) ($conv['ttclid'] ?? '');
                if (!TikTokService::isTikTokClickIdValid($ttclidRaw)) {
                    $this->failConv($pdo, $conv['id'], ['error' => 'Invalid ttclid']);
                    $failed++;
                    $results[] = ['id' => $conv['id'], 'status' => 'failed'];
                    continue;
                }
                $url = (string) ($conv['url'] ?? '');
                if (!TikTokService::isAbsoluteHttpUrl($url)) {
                    $this->failConv($pdo, $conv['id'], ['error' => 'Invalid page.url']);
                    $failed++;
                    $results[] = ['id' => $conv['id'], 'status' => 'failed'];
                    continue;
                }
                $ttclid = TikTokService::normalizeTikTokClickId($ttclidRaw);
                $eventId = trim((string) ($conv['event_id'] ?? ''));
                if ($eventId === '') {
                    $eventId = 'evt_' . Database::uuid();
                }
                $properties = [];
                if (!empty($conv['currency'])) {
                    $properties['currency'] = $conv['currency'];
                }
                if ($conv['value'] !== null && $conv['value'] !== '') {
                    $properties['value'] = (float) $conv['value'];
                }
                if (!empty($conv['content_type'])) {
                    $properties['contentType'] = $conv['content_type'];
                }
                if (!empty($conv['contents'])) {
                    $decoded = json_decode((string) $conv['contents'], true);
                    $properties['contents'] = $decoded ?? $conv['contents'];
                }
                $pageUrl = TikTokService::ensureTtclidInUrl($url, $ttclid);
                $page = ['url' => $pageUrl];
                if (!empty($conv['referrer'])) {
                    $page['referrer'] = (string) $conv['referrer'];
                }
                $event = [
                    'event' => $conv['event_name'],
                    'event_time' => (int) floor((new DateTimeImmutable((string) $conv['event_time']))->getTimestamp()),
                    'event_id' => $eventId,
                    'user' => [],
                    'page' => $page,
                ];
                $eventUser = [];
                $eventUser['ttclid'] = $ttclid;
                if (!empty($conv['email'])) {
                    $eventUser['email'] = TikTokService::hashEmail((string) $conv['email']);
                }
                if (!empty($conv['phone'])) {
                    $eventUser['phone'] = TikTokService::hashPhone((string) $conv['phone']);
                }
                if (!empty($conv['external_id'])) {
                    $eventUser['external_id'] = TikTokService::hashExternalId((string) $conv['external_id']);
                }
                if (!empty($conv['ip'])) {
                    $eventUser['ip'] = (string) $conv['ip'];
                }
                if (!empty($conv['user_agent'])) {
                    $eventUser['user_agent'] = (string) $conv['user_agent'];
                }
                $event['user'] = $eventUser;
                if ($properties !== []) {
                    $propsOut = [];
                    if (isset($properties['currency'])) {
                        $propsOut['currency'] = $properties['currency'];
                    }
                    if (isset($properties['value'])) {
                        $propsOut['value'] = $properties['value'];
                    }
                    if (isset($properties['contentType'])) {
                        $propsOut['content_type'] = $properties['contentType'];
                    }
                    if (isset($properties['contents'])) {
                        $propsOut['contents'] = $properties['contents'];
                    }
                    $event['properties'] = $propsOut;
                }
                $prepared[] = ['id' => $conv['id'], 'eventId' => $eventId, 'ttclid' => $ttclid, 'event' => $event];
            }

            $chunks = array_chunk($prepared, 1000);
            foreach ($chunks as $chunk) {
                $dataPayload = array_column($chunk, 'event');
                $payload = [
                    'event_source' => 'web',
                    'event_source_id' => $config['pixelId'],
                    'data' => $dataPayload,
                ];
                $ch = curl_init('https://business-api.tiktok.com/open_api/v1.3/event/track/');
                curl_setopt_array($ch, [
                    CURLOPT_POST => true,
                    CURLOPT_HTTPHEADER => [
                        'Access-Token: ' . $config['accessToken'],
                        'Content-Type: application/json',
                    ],
                    CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 120,
                ]);
                $body = curl_exec($ch);
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                $responseData = json_decode((string) $body, true);
                if (!is_array($responseData)) {
                    $responseData = ['raw' => $body];
                }
                $ok = $code >= 200 && $code < 300 && (($responseData['code'] ?? -1) === 0);
                $status = $ok ? 'sent' : 'failed';
                $respJson = json_encode($responseData, JSON_THROW_ON_ERROR);
                foreach ($chunk as $item) {
                    $upd = $pdo->prepare('UPDATE conversions SET status = ?, response = ?, event_id = ?, ttclid = ? WHERE id = ?');
                    $upd->execute([$status, $respJson, $item['eventId'], $item['ttclid'], $item['id']]);
                    if ($ok) {
                        $sent++;
                    } else {
                        $failed++;
                    }
                    $results[] = ['id' => $item['id'], 'status' => $status];
                }
            }
        }
        return ['sent' => $sent, 'failed' => $failed, 'results' => $results];
    }

    /** @param array<string, mixed> $failure */
    private function failConv(PDO $pdo, string $id, array $failure): void
    {
        $pdo->prepare('UPDATE conversions SET status = ?, response = ? WHERE id = ?')->execute([
            'failed',
            json_encode($failure, JSON_THROW_ON_ERROR),
            $id,
        ]);
    }

    /** @param array<string, mixed> $conv */
    public static function buildPreviewPayload(array $conv): array
    {
        $ttclidRaw = trim((string) ($conv['ttclid'] ?? ''));
        $user = [];
        if ($ttclidRaw !== '') {
            $user['ttclid'] = $ttclidRaw;
        }
        if (!empty($conv['email'])) {
            $user['email'] = '[sha256(' . $conv['email'] . ')]';
        }
        if (!empty($conv['phone'])) {
            $user['phone'] = '[sha256(' . $conv['phone'] . ')]';
        }
        if (!empty($conv['external_id'])) {
            $user['external_id'] = '[sha256(' . $conv['external_id'] . ')]';
        }
        if (!empty($conv['ip'])) {
            $user['ip'] = $conv['ip'];
        }
        if (!empty($conv['user_agent'])) {
            $user['user_agent'] = $conv['user_agent'];
        }
        $properties = [];
        if (!empty($conv['currency'])) {
            $properties['currency'] = $conv['currency'];
        }
        if (isset($conv['value']) && $conv['value'] !== null && $conv['value'] !== '') {
            $properties['value'] = (float) $conv['value'];
        }
        if (!empty($conv['content_type'])) {
            $properties['content_type'] = $conv['content_type'];
        }
        if (!empty($conv['contents'])) {
            $decoded = is_string($conv['contents']) ? json_decode($conv['contents'], true) : $conv['contents'];
            $properties['contents'] = $decoded ?? $conv['contents'];
        }
        $finalUrl = (string) ($conv['url'] ?? '');
        if ($finalUrl !== '' && $ttclidRaw !== '') {
            $finalUrl = TikTokService::ensureTtclidInUrl($finalUrl, $ttclidRaw);
        }
        $event = [
            'event' => $conv['event_name'],
            'event_time' => (int) floor((new \DateTimeImmutable((string) $conv['event_time']))->getTimestamp()),
            'event_id' => trim((string) ($conv['event_id'] ?? '')) ?: '[auto-generated at send time]',
            'user' => $user,
            'page' => array_filter([
                'url' => $finalUrl,
                'referrer' => $conv['referrer'] ?? null,
            ]),
        ];
        if ($properties !== []) {
            $event['properties'] = $properties;
        }
        return [
            'event_source' => 'web',
            'event_source_id' => $conv['pixel_id'],
            'data' => [$event],
        ];
    }

    private static function parseEventTime(mixed $value): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }
        if (is_int($value) || (is_string($value) && is_numeric($value))) {
            $n = (int) $value;
            $ms = $n > 1_000_000_000_000;
            $ts = $ms ? (int) ($n / 1000) : $n;
            if ($n < 1_000_000_000_000) {
                $ts = $n;
            }
            $d = DateTimeImmutable::createFromFormat('U.u', sprintf('%d.%06d', $ts, 0));
            return $d ?: new DateTimeImmutable('@' . $ts);
        }
        if (is_string($value) && trim($value) !== '') {
            $d = new DateTimeImmutable($value);
            return $d;
        }
        return null;
    }

    public function apiList(): void
    {
        Auth::requireAuth();
        $pdo = Database::pdo();
        $u = Auth::user();
        $isAdmin = $u['role'] === 'admin';
        $sql = 'SELECT c.*, tc.pixel_id, tc.event_name AS config_event, u.name AS user_name FROM conversions c
            JOIN tiktok_configs tc ON tc.id = c.config_id
            JOIN users u ON u.id = c.user_id';
        $sql .= $isAdmin ? ' ORDER BY c.created_at DESC LIMIT 100' : ' WHERE c.user_id = ? ORDER BY c.created_at DESC LIMIT 100';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($isAdmin ? [] : [$u['id']]);
        View::json($stmt->fetchAll());
    }

    public function apiStore(): void
    {
        Auth::requireAuth();
        $raw = file_get_contents('php://input');
        $body = json_decode((string) $raw, true);
        if (!is_array($body)) {
            View::json(['error' => 'Invalid JSON'], 400);
        }
        $_POST = array_merge($_POST, [
            'config_id' => $body['configId'] ?? '',
            'event_name' => $body['events'][0]['eventName'] ?? 'Purchase',
            'event_time' => $body['events'][0]['eventTime'] ?? '',
            'event_id' => $body['events'][0]['eventId'] ?? '',
            'ttclid' => $body['events'][0]['ttclid'] ?? '',
            'url' => $body['events'][0]['url'] ?? '',
            'email' => $body['events'][0]['email'] ?? '',
            'phone' => $body['events'][0]['phone'] ?? '',
            'external_id' => $body['events'][0]['externalId'] ?? '',
            'ip' => $body['events'][0]['ip'] ?? '',
            'user_agent' => $body['events'][0]['userAgent'] ?? '',
            'referrer' => $body['events'][0]['referrer'] ?? '',
            'currency' => $body['events'][0]['currency'] ?? '',
            'value' => $body['events'][0]['value'] ?? '',
            'content_type' => $body['events'][0]['contentType'] ?? 'product',
        ]);
        if (!Csrf::verify($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            View::json(['error' => 'CSRF'], 403);
        }
        // reuse validation by calling store logic inline would redirect — duplicate minimal:
        // For API, return JSON errors
        $pdo = Database::pdo();
        $configId = (string) ($body['configId'] ?? '');
        $events = $body['events'] ?? [];
        if ($configId === '' || !is_array($events) || $events === []) {
            View::json(['error' => 'Config ID and events required'], 400);
        }
        $stmt = $pdo->prepare('SELECT * FROM tiktok_configs WHERE id = ?');
        $stmt->execute([$configId]);
        $cfg = $stmt->fetch();
        if (!$cfg || ($cfg['user_id'] !== Auth::user()['id'] && Auth::user()['role'] !== 'admin')) {
            View::json(['error' => 'Unauthorized'], 401);
        }
        $mapped = [];
        $errors = [];
        foreach ($events as $idx => $event) {
            $row = $idx + 1;
            $ttclid = trim((string) ($event['ttclid'] ?? ''));
            if (!TikTokService::isTikTokClickIdValid($ttclid)) {
                $errors[] = "Row $row: ttclid invalid";
                continue;
            }
            $et = self::parseEventTime($event['eventTime'] ?? null);
            if ($et === null) {
                $errors[] = "Row $row: eventTime invalid";
                continue;
            }
            $url = trim((string) ($event['url'] ?? ''));
            if (!TikTokService::isAbsoluteHttpUrl($url)) {
                $errors[] = "Row $row: url invalid";
                continue;
            }
            $eventName = trim((string) ($event['eventName'] ?? 'Purchase')) ?: 'Purchase';
            $purchase = in_array($eventName, ['Purchase', 'CompletePayment', 'PlaceAnOrder'], true);
            $valRaw = $event['value'] ?? null;
            $currency = strtoupper(trim((string) ($event['currency'] ?? '')));
            $value = null;
            if ($valRaw !== null && $valRaw !== '' && is_numeric($valRaw)) {
                $value = (float) $valRaw;
            }
            if ($purchase && ($value === null || $value <= 0)) {
                $errors[] = "Row $row: value required";
                continue;
            }
            if ($purchase && $currency === '') {
                $errors[] = "Row $row: currency required";
                continue;
            }
            $eventId = trim((string) ($event['eventId'] ?? ''));
            if ($eventId === '') {
                $eventId = 'manual_' . Database::uuid();
            }
            $mapped[] = [
                Database::uuid(),
                Auth::user()['id'],
                $configId,
                $eventName,
                $et->format('Y-m-d H:i:s.u'),
                $eventId,
                TikTokService::normalizeTikTokClickId($ttclid),
                $event['email'] ?? null,
                $event['phone'] ?? null,
                $event['externalId'] ?? null,
                $event['ip'] ?? null,
                $event['userAgent'] ?? null,
                $url,
                $event['referrer'] ?? null,
                $currency ?: null,
                $value,
                $event['contentType'] ?? 'product',
                null,
            ];
        }
        if ($errors !== []) {
            View::json(['error' => $errors[0], 'details' => array_slice($errors, 0, 10)], 400);
        }
        $ins = $pdo->prepare(
            'INSERT INTO conversions (id, user_id, config_id, event_name, event_time, event_id, ttclid, email, phone, external_id, ip, user_agent, url, referrer, currency, value, content_type, contents, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,\'pending\')'
        );
        foreach ($mapped as $m) {
            $ins->execute($m);
        }
        View::json(['message' => count($mapped) . ' conversions created', 'count' => count($mapped)]);
    }

    public function apiSend(): void
    {
        Auth::requireAuth();
        $raw = file_get_contents('php://input');
        $body = json_decode((string) $raw, true);
        if (!is_array($body)) {
            View::json(['error' => 'Invalid JSON'], 400);
        }
        if (!Csrf::verify($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            View::json(['error' => 'CSRF'], 403);
        }
        $ids = $body['conversionIds'] ?? [];
        if (!is_array($ids) || $ids === []) {
            View::json(['error' => 'Conversion IDs required'], 400);
        }
        $r = $this->processSendIds(array_map('strval', $ids));
        View::json(['results' => $r['results'], 'sent' => $r['sent'], 'failed' => $r['failed']]);
    }
}
