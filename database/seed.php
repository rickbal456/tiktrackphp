<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::pdo();

$roles = [
    ['admin', 'Administrator with full access'],
    ['user', 'Regular user'],
];
foreach ($roles as [$name, $desc]) {
    $stmt = $pdo->prepare('SELECT id FROM roles WHERE name = ?');
    $stmt->execute([$name]);
    if (!$stmt->fetchColumn()) {
        $rid = Database::uuid();
        $pdo->prepare('INSERT INTO roles (id, name, description) VALUES (?,?,?)')->execute([$rid, $name, $desc]);
    }
}

$stmt = $pdo->prepare('SELECT id FROM roles WHERE name = ?');
$stmt->execute(['admin']);
$adminRoleId = (string) $stmt->fetchColumn();
if ($adminRoleId === '') {
    throw new RuntimeException('admin role missing');
}

$email = 'admin@tiktrack.local';
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetchColumn()) {
    echo "Admin already exists: $email\n";
    exit(0);
}

$hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
$uid = Database::uuid();
$pdo->prepare(
    'INSERT INTO users (id, name, email, password, role_id) VALUES (?,?,?,?,?)'
)->execute([$uid, 'Admin', $email, $hash, $adminRoleId]);

echo "Seed complete:\n";
echo "  Roles: admin, user\n";
echo "  Admin: $email / admin123\n";
