<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireAuth();
        $pdo = Database::pdo();
        $u = Auth::user();
        $isAdmin = $u['role'] === 'admin';
        $uid = $u['id'];

        $whereUser = $isAdmin ? '1=1' : 'user_id = :uid';
        $whereUserAliased = $isAdmin ? '1=1' : 'c.user_id = :uid';
        $params = $isAdmin ? [] : ['uid' => $uid];

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM conversions WHERE $whereUser");
        $stmt->execute($params);
        $totalConversions = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM conversions WHERE $whereUser AND status = 'sent'");
        $stmt->execute($params);
        $sentConversions = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM conversions WHERE $whereUser AND status = 'failed'");
        $stmt->execute($params);
        $failedConversions = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM conversions WHERE $whereUser AND status = 'pending'");
        $stmt->execute($params);
        $pendingConversions = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tiktok_configs WHERE $whereUser");
        $stmt->execute($params);
        $totalConfigs = (int) $stmt->fetchColumn();

        $totalUsers = 0;
        if ($isAdmin) {
            $totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM landing_pages WHERE $whereUser");
        $stmt->execute($params);
        $totalLandingPages = (int) $stmt->fetchColumn();

        $sqlRecent = "SELECT c.*, tc.pixel_id, u.name AS user_name FROM conversions c
            JOIN tiktok_configs tc ON tc.id = c.config_id
            JOIN users u ON u.id = c.user_id
            WHERE $whereUserAliased
            ORDER BY c.created_at DESC LIMIT 10";
        $stmt = $pdo->prepare($sqlRecent);
        $stmt->execute($params);
        $recent = $stmt->fetchAll();

        View::dashboard('dashboard.index', [
            'title' => $isAdmin ? 'Admin Dashboard' : 'Dashboard',
            'stats' => [
                'totalConversions' => $totalConversions,
                'sentConversions' => $sentConversions,
                'failedConversions' => $failedConversions,
                'pendingConversions' => $pendingConversions,
                'totalConfigs' => $totalConfigs,
                'totalUsers' => $totalUsers,
                'totalLandingPages' => $totalLandingPages,
            ],
            'recentConversions' => $recent,
            'isAdmin' => $isAdmin,
        ]);
    }
}
