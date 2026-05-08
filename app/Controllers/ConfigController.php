<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;

final class ConfigController
{
    public function index(): void
    {
        Auth::requireAuth();
        $pdo = Database::pdo();
        $u = Auth::user();
        $isAdmin = $u['role'] === 'admin';
        $sql = 'SELECT tc.*, u.name AS user_name, u.email AS user_email FROM tiktok_configs tc JOIN users u ON u.id = tc.user_id';
        $sql .= $isAdmin ? ' ORDER BY tc.created_at DESC' : ' WHERE tc.user_id = ? ORDER BY tc.created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($isAdmin ? [] : [$u['id']]);
        $configs = $stmt->fetchAll();
        View::dashboard('config.index', [
            'title' => 'TikTok Configuration',
            'configs' => $configs,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Auth::flash('error', 'Invalid CSRF');
            View::redirect('/config');
        }
        $access = trim((string) ($_POST['access_token'] ?? ''));
        $pixel = trim((string) ($_POST['pixel_id'] ?? ''));
        $eventName = trim((string) ($_POST['event_name'] ?? 'Purchase'));
        if ($access === '' || $pixel === '') {
            Auth::flash('error', 'Access Token and Pixel ID are required');
            View::redirect('/config');
        }
        $pdo = Database::pdo();
        $id = Database::uuid();
        $pdo->prepare(
            'INSERT INTO tiktok_configs (id, user_id, access_token, pixel_id, event_name, is_active) VALUES (?,?,?,?,?,1)'
        )->execute([$id, Auth::user()['id'], $access, $pixel, $eventName ?: 'Purchase']);
        Auth::flash('success', 'Config created');
        View::redirect('/config');
    }

    public function update(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Auth::flash('error', 'Invalid CSRF');
            View::redirect('/config');
        }
        $id = (string) ($_POST['id'] ?? '');
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM tiktok_configs WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row || ($row['user_id'] !== Auth::user()['id'] && Auth::user()['role'] !== 'admin')) {
            Auth::flash('error', 'Unauthorized');
            View::redirect('/config');
        }
        $access = trim((string) ($_POST['access_token'] ?? ''));
        $pixel = trim((string) ($_POST['pixel_id'] ?? ''));
        $eventName = trim((string) ($_POST['event_name'] ?? ''));
        $data = [];
        if ($access !== '') {
            $data['access_token'] = $access;
        }
        if ($pixel !== '') {
            $data['pixel_id'] = $pixel;
        }
        if ($eventName !== '') {
            $data['event_name'] = $eventName;
        }
        if ($data === []) {
            View::redirect('/config');
        }
        $sets = [];
        foreach ($data as $k => $v) {
            $sets[] = "$k = ?";
        }
        $vals = array_values($data);
        $vals[] = $id;
        $pdo->prepare('UPDATE tiktok_configs SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($vals);
        Auth::flash('success', 'Config updated');
        View::redirect('/config');
    }

    public function toggle(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            View::redirect('/config');
        }
        $id = (string) ($_POST['id'] ?? '');
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT user_id, is_active FROM tiktok_configs WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row || ($row['user_id'] !== Auth::user()['id'] && Auth::user()['role'] !== 'admin')) {
            View::redirect('/config');
        }
        $new = (int) $row['is_active'] ? 0 : 1;
        $pdo->prepare('UPDATE tiktok_configs SET is_active = ? WHERE id = ?')->execute([$new, $id]);
        View::redirect('/config');
    }

    public function delete(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            View::redirect('/config');
        }
        $id = (string) ($_POST['id'] ?? '');
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT user_id FROM tiktok_configs WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row || ($row['user_id'] !== Auth::user()['id'] && Auth::user()['role'] !== 'admin')) {
            View::redirect('/config');
        }
        $pdo->prepare('DELETE FROM tiktok_configs WHERE id = ?')->execute([$id]);
        Auth::flash('success', 'Config deleted');
        View::redirect('/config');
    }
}
