<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;
use App\Services\DomainVerifier;

final class DomainController
{
    public function index(): void
    {
        Auth::requireAuth();
        $pdo = Database::pdo();
        $u = Auth::user();
        $isAdmin = $u['role'] === 'admin';
        $sql = 'SELECT d.*, (SELECT COUNT(*) FROM landing_page_domains lpd WHERE lpd.custom_domain_id = d.id) AS lp_count FROM custom_domains d';
        $sql .= $isAdmin ? ' ORDER BY d.created_at DESC' : ' WHERE d.user_id = ? ORDER BY d.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($isAdmin ? [] : [$u['id']]);
        $domains = $stmt->fetchAll();
        View::dashboard('domains.index', ['title' => 'Custom Domains', 'domains' => $domains]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Auth::flash('error', 'Invalid CSRF');
            View::redirect('/domains');
        }
        $raw = trim((string) ($_POST['domain'] ?? ''));
        $normalized = DomainVerifier::normalizeDomain($raw);
        if ($normalized === '' || !DomainVerifier::isDomainFormatValid($normalized)) {
            Auth::flash('error', 'Invalid domain format');
            View::redirect('/domains');
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id FROM custom_domains WHERE domain = ?');
        $stmt->execute([$normalized]);
        if ($stmt->fetchColumn()) {
            Auth::flash('error', 'Domain already claimed');
            View::redirect('/domains');
        }
        $token = 'tiktrack-verify=' . substr(rtrim(strtr(base64_encode($normalized . '-' . microtime(true)), '+/', '-_'), '='), 0, 32);
        $id = Database::uuid();
        $pdo->prepare(
            'INSERT INTO custom_domains (id, user_id, domain, status, verification_method, verification_token, ssl_mode) VALUES (?,?,?,?,?,?,?)'
        )->execute([$id, Auth::user()['id'], $normalized, 'pending', 'dns_a', $token, 'full']);
        Auth::flash('success', 'Domain added. Configure DNS then click Verify.');
        View::redirect('/domains');
    }

    public function verify(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Auth::flash('error', 'Invalid CSRF');
            View::redirect('/domains');
        }
        $id = (string) ($_POST['id'] ?? '');
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM custom_domains WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row || ($row['user_id'] !== Auth::user()['id'] && Auth::user()['role'] !== 'admin')) {
            Auth::flash('error', 'Unauthorized');
            View::redirect('/domains');
        }
        $method = $row['verification_method'] ?? 'dns_a';
        if ($method === 'dns_txt' && !empty($row['verification_token'])) {
            $result = DomainVerifier::verifyByTxt($row['domain'], (string) $row['verification_token']);
        } else {
            $result = DomainVerifier::verifyByARecord($row['domain']);
        }
        if (!$result['ok']) {
            $pdo->prepare('UPDATE custom_domains SET status = ?, verification_error = ? WHERE id = ?')->execute([
                'failed',
                $result['error'] ?? 'Verification failed',
                $id,
            ]);
            Auth::flash('error', $result['error'] ?? 'Verification failed');
            View::redirect('/domains');
        }
        $pdo->prepare('UPDATE custom_domains SET status = ?, verification_error = NULL, verified_at = NOW(3) WHERE id = ?')->execute(['verified', $id]);
        Auth::flash('success', 'Domain verified');
        View::redirect('/domains');
    }

    public function delete(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            View::redirect('/domains');
        }
        $id = (string) ($_POST['id'] ?? '');
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT user_id FROM custom_domains WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row || ($row['user_id'] !== Auth::user()['id'] && Auth::user()['role'] !== 'admin')) {
            View::redirect('/domains');
        }
        $pdo->prepare('DELETE FROM custom_domains WHERE id = ?')->execute([$id]);
        Auth::flash('success', 'Domain deleted');
        View::redirect('/domains');
    }
}
