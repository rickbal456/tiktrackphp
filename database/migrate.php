<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::pdo();
$migrationsDir = dirname(__DIR__) . '/database/migrations';
$files = glob($migrationsDir . '/*.sql') ?: [];
sort($files);

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL UNIQUE,
        ran_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

foreach ($files as $file) {
    $name = basename($file);
    $stmt = $pdo->prepare('SELECT 1 FROM migrations WHERE filename = ?');
    $stmt->execute([$name]);
    if ($stmt->fetchColumn()) {
        echo "Skip: $name\n";
        continue;
    }
    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException("Cannot read $file");
    }
    try {
        $pdo->exec($sql);
        $ins = $pdo->prepare('INSERT INTO migrations (filename) VALUES (?)');
        $ins->execute([$name]);
        echo "OK: $name\n";
    } catch (Throwable $e) {
        throw $e;
    }
}

echo "Migrations complete.\n";
