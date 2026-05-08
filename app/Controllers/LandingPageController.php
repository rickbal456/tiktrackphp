<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;

final class LandingPageController
{
    public function index(): void
    {
        Auth::requireAuth();
        $pdo = Database::pdo();
        $u = Auth::user();
        $isAdmin = $u['role'] === 'admin';
        $sql = 'SELECT lp.*, tc.pixel_id, tc.event_name AS cfg_event, usr.name AS owner_name FROM landing_pages lp
            LEFT JOIN tiktok_configs tc ON tc.id = lp.config_id
            JOIN users usr ON usr.id = lp.user_id';
        $sql .= $isAdmin ? ' ORDER BY lp.created_at DESC' : ' WHERE lp.user_id = ? ORDER BY lp.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($isAdmin ? [] : [$u['id']]);
        $pages = $stmt->fetchAll();
        foreach ($pages as &$p) {
            $s = $pdo->prepare('SELECT * FROM landing_page_sections WHERE landing_page_id = ? ORDER BY sort_order ASC');
            $s->execute([$p['id']]);
            $p['sections'] = $s->fetchAll();
            $d = $pdo->prepare(
                'SELECT lpd.*, cd.domain, cd.status FROM landing_page_domains lpd
                 JOIN custom_domains cd ON cd.id = lpd.custom_domain_id
                 WHERE lpd.landing_page_id = ?'
            );
            $d->execute([$p['id']]);
            $p['domain_links'] = $d->fetchAll();
        }
        unset($p);

        $cfgs = $pdo->prepare($isAdmin ? 'SELECT * FROM tiktok_configs WHERE is_active = 1 ORDER BY created_at DESC' : 'SELECT * FROM tiktok_configs WHERE user_id = ? AND is_active = 1 ORDER BY created_at DESC');
        $cfgs->execute($isAdmin ? [] : [$u['id']]);
        $configs = $cfgs->fetchAll();

        $domStmt = $pdo->prepare("SELECT * FROM custom_domains WHERE user_id = ? AND status = 'verified' ORDER BY created_at DESC");
        $domStmt->execute([$u['id']]);
        $verifiedDomains = $domStmt->fetchAll();

        View::dashboard('landing-pages.index', [
            'title' => 'Landing Pages',
            'landingPages' => $pages,
            'configs' => $configs,
            'verifiedDomains' => $verifiedDomains,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Auth::flash('error', 'Invalid CSRF');
            View::redirect('/landing-pages');
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = $this->normalizeSlug((string) ($_POST['slug'] ?? ''));
        if ($name === '' || $slug === '') {
            Auth::flash('error', 'Name and slug required');
            View::redirect('/landing-pages');
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id FROM landing_pages WHERE slug = ?');
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn()) {
            Auth::flash('error', 'Slug already exists');
            View::redirect('/landing-pages');
        }
        $domainIds = $this->normalizeDomainIds($_POST['domain_ids'] ?? []);
        if (!$this->validateDomainIds($pdo, $domainIds, Auth::user()['id'])) {
            Auth::flash('error', 'Invalid or unverified domain selection');
            View::redirect('/landing-pages');
        }
        $configId = trim((string) ($_POST['config_id'] ?? ''));
        $configId = $configId === '' ? null : $configId;
        if ($configId !== null && !$this->ownsConfig($pdo, $configId, Auth::user()['id'])) {
            Auth::flash('error', 'Invalid config');
            View::redirect('/landing-pages');
        }
        $sections = $this->parseSections($_POST['sections'] ?? null);
        $lpId = Database::uuid();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO landing_pages (id, user_id, config_id, name, slug, is_active, click_id_param, event_name, page_title)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            )->execute([
                $lpId,
                Auth::user()['id'],
                $configId,
                $name,
                $slug,
                1,
                trim((string) ($_POST['click_id_param'] ?? 'ttclid')) ?: 'ttclid',
                trim((string) ($_POST['event_name'] ?? 'ClickButton')) ?: 'ClickButton',
                trim((string) ($_POST['page_title'] ?? 'Please Wait...')) ?: 'Please Wait...',
            ]);
            $this->insertSections($pdo, $lpId, $sections);
            $this->syncDomains($pdo, $lpId, $domainIds);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            Auth::flash('error', 'Save failed: ' . $e->getMessage());
            View::redirect('/landing-pages');
        }
        Auth::flash('success', 'Landing page created');
        View::redirect('/landing-pages');
    }

    public function update(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Auth::flash('error', 'Invalid CSRF');
            View::redirect('/landing-pages');
        }
        $id = (string) ($_POST['id'] ?? '');
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM landing_pages WHERE id = ?');
        $stmt->execute([$id]);
        $lp = $stmt->fetch();
        if (!$lp || ($lp['user_id'] !== Auth::user()['id'] && Auth::user()['role'] !== 'admin')) {
            Auth::flash('error', 'Unauthorized');
            View::redirect('/landing-pages');
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = $this->normalizeSlug((string) ($_POST['slug'] ?? ''));
        if ($name === '' || $slug === '') {
            Auth::flash('error', 'Name and slug required');
            View::redirect('/landing-pages');
        }
        if ($slug !== $lp['slug']) {
            $chk = $pdo->prepare('SELECT id FROM landing_pages WHERE slug = ? AND id <> ?');
            $chk->execute([$slug, $id]);
            if ($chk->fetchColumn()) {
                Auth::flash('error', 'Slug already exists');
                View::redirect('/landing-pages');
            }
        }
        $domainIds = $this->normalizeDomainIds($_POST['domain_ids'] ?? []);
        if (!$this->validateDomainIds($pdo, $domainIds, (string) $lp['user_id'])) {
            Auth::flash('error', 'Invalid or unverified domain selection');
            View::redirect('/landing-pages');
        }
        $configId = trim((string) ($_POST['config_id'] ?? ''));
        $configId = $configId === '' ? null : $configId;
        if ($configId !== null && !$this->ownsConfig($pdo, $configId, (string) $lp['user_id'])) {
            Auth::flash('error', 'Invalid config');
            View::redirect('/landing-pages');
        }
        $sections = $this->parseSections($_POST['sections'] ?? null);
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'UPDATE landing_pages SET name=?, slug=?, config_id=?, event_name=?, page_title=?, click_id_param=?, is_active=? WHERE id=?'
            )->execute([
                $name,
                $slug,
                $configId,
                trim((string) ($_POST['event_name'] ?? 'ClickButton')) ?: 'ClickButton',
                trim((string) ($_POST['page_title'] ?? 'Please Wait...')) ?: 'Please Wait...',
                trim((string) ($_POST['click_id_param'] ?? 'ttclid')) ?: 'ttclid',
                (isset($_POST['is_active']) && (string) $_POST['is_active'] === '1') ? 1 : 0,
                $id,
            ]);
            $pdo->prepare('DELETE FROM landing_page_sections WHERE landing_page_id = ?')->execute([$id]);
            $this->insertSections($pdo, $id, $sections);
            $pdo->prepare('DELETE FROM landing_page_domains WHERE landing_page_id = ?')->execute([$id]);
            $this->syncDomains($pdo, $id, $domainIds);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            Auth::flash('error', 'Update failed: ' . $e->getMessage());
            View::redirect('/landing-pages');
        }
        Auth::flash('success', 'Landing page updated');
        View::redirect('/landing-pages');
    }

    public function delete(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            View::redirect('/landing-pages');
        }
        $id = (string) ($_POST['id'] ?? '');
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT user_id FROM landing_pages WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row || ($row['user_id'] !== Auth::user()['id'] && Auth::user()['role'] !== 'admin')) {
            View::redirect('/landing-pages');
        }
        $pdo->prepare('DELETE FROM landing_pages WHERE id = ?')->execute([$id]);
        Auth::flash('success', 'Landing page deleted');
        View::redirect('/landing-pages');
    }

    private function normalizeSlug(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
        return trim($s, '-');
    }

    /** @param mixed $ids */
    private function normalizeDomainIds(mixed $ids): array
    {
        if (!is_array($ids)) {
            return [];
        }
        $out = [];
        foreach ($ids as $x) {
            $x = (string) $x;
            if ($x !== '') {
                $out[] = $x;
            }
        }
        return array_values(array_unique($out));
    }

    /** @param list<string> $domainIds */
    private function validateDomainIds(\PDO $pdo, array $domainIds, string $userId): bool
    {
        if ($domainIds === []) {
            return true;
        }
        $ph = implode(',', array_fill(0, count($domainIds), '?'));
        $params = array_merge($domainIds, [$userId]);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM custom_domains WHERE id IN ($ph) AND user_id = ? AND status = 'verified'");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() === count($domainIds);
    }

    private function ownsConfig(\PDO $pdo, string $configId, string $userId): bool
    {
        $stmt = $pdo->prepare('SELECT user_id FROM tiktok_configs WHERE id = ?');
        $stmt->execute([$configId]);
        $uid = $stmt->fetchColumn();
        return (string) $uid === $userId;
    }

    /** @return list<array{name: string, urls: list<string>, delayMs: int, sortOrder: int, isActive: bool}> */
    private function parseSections(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            $urlsText = (string) ($row['urls_text'] ?? '');
            $urls = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $urlsText) ?: [])));
            if ($name === '' || $urls === []) {
                continue;
            }
            $out[] = [
                'name' => $name,
                'urls' => $urls,
                'delayMs' => max(1, (int) ($row['delay_ms'] ?? 1)),
                'sortOrder' => (int) ($row['sort_order'] ?? $i),
                'isActive' => isset($row['is_active']) && (string) $row['is_active'] === '1',
            ];
        }
        return $out;
    }

    /** @param list<array{name: string, urls: list<string>, delayMs: int, sortOrder: int, isActive: bool}> $sections */
    private function insertSections(\PDO $pdo, string $lpId, array $sections): void
    {
        $ins = $pdo->prepare(
            'INSERT INTO landing_page_sections (id, landing_page_id, name, urls, delay_ms, sort_order, is_active) VALUES (?,?,?,?,?,?,?)'
        );
        foreach ($sections as $i => $s) {
            $ins->execute([
                Database::uuid(),
                $lpId,
                $s['name'],
                json_encode($s['urls'], JSON_THROW_ON_ERROR),
                $s['delayMs'],
                $s['sortOrder'] ?? $i,
                $s['isActive'] ? 1 : 0,
            ]);
        }
    }

    /** @param list<string> $domainIds */
    private function syncDomains(\PDO $pdo, string $lpId, array $domainIds): void
    {
        foreach ($domainIds as $i => $did) {
            $pdo->prepare(
                'INSERT INTO landing_page_domains (id, landing_page_id, custom_domain_id, is_primary) VALUES (?,?,?,?)'
            )->execute([Database::uuid(), $lpId, $did, $i === 0 ? 1 : 0]);
        }
    }
}
