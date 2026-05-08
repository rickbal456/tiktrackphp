<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Controllers\LpController;
use App\Core\Auth;
use App\Core\Database;
use App\Core\Host;
use App\Core\Router;
use App\Core\View;

Auth::start();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($uri, PHP_URL_PATH) ?: '/';
$path = '/' . trim($path, '/');
if ($path === '//') {
    $path = '/';
}

$host = Host::current();

// Public landing page: /lp/{slug}
if (preg_match('#^/lp/([^/]+)/?$#', $path, $m)) {
    (new LpController())->show($m[1]);
    return;
}

// Apex custom domain → first active landing page
if ($path === '/' && $host !== '' && !Host::isSaasHost($host)) {
    try {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT lp.slug FROM custom_domains d
             INNER JOIN landing_page_domains lpd ON lpd.custom_domain_id = d.id
             INNER JOIN landing_pages lp ON lp.id = lpd.landing_page_id
             WHERE d.domain = ? AND d.status = ? AND lp.is_active = 1
             ORDER BY lpd.is_primary DESC, lpd.created_at ASC
             LIMIT 1'
        );
        $stmt->execute([$host, 'verified']);
        $slug = $stmt->fetchColumn();
        if (is_string($slug) && $slug !== '') {
            View::redirect('/lp/' . rawurlencode($slug) . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : ''));
        }
    } catch (\Throwable) {
        // DB not ready — fall through
    }
}

// SaaS home
if ($path === '/') {
    if (Auth::check()) {
        View::redirect('/dashboard');
    }
    View::redirect('/login');
}

$router = new Router(static function (): void {
    http_response_code(404);
    echo 'Not Found';
});

$register = require dirname(__DIR__) . '/routes/web.php';
$register($router);

$router->dispatch($method, $uri);
