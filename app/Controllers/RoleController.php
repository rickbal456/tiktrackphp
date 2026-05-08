<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;

final class RoleController
{
    public function index(): void
    {
        Auth::requireAdmin();
        $pdo = Database::pdo();
        $roles = $pdo->query(
            'SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) AS user_count FROM roles r ORDER BY r.created_at ASC'
        )->fetchAll();
        $users = $pdo->query(
            'SELECT u.id, u.name, u.email, r.name AS role, u.created_at FROM users u JOIN roles r ON r.id = u.role_id ORDER BY u.created_at DESC'
        )->fetchAll();
        View::dashboard('roles.index', ['title' => 'Role Management', 'roles' => $roles, 'users' => $users]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Auth::flash('error', 'Invalid CSRF');
            View::redirect('/roles');
        }
        $name = strtolower(trim((string) ($_POST['name'] ?? '')));
        $desc = trim((string) ($_POST['description'] ?? ''));
        if ($name === '') {
            Auth::flash('error', 'Role name required');
            View::redirect('/roles');
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id FROM roles WHERE name = ?');
        $stmt->execute([$name]);
        if ($stmt->fetchColumn()) {
            Auth::flash('error', 'Role already exists');
            View::redirect('/roles');
        }
        $pdo->prepare('INSERT INTO roles (id, name, description) VALUES (?,?,?)')->execute([Database::uuid(), $name, $desc ?: null]);
        Auth::flash('success', 'Role created');
        View::redirect('/roles');
    }

    public function delete(): void
    {
        Auth::requireAdmin();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            View::redirect('/roles');
        }
        $id = (string) ($_POST['id'] ?? '');
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT name, (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) AS c FROM roles r WHERE r.id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            View::redirect('/roles');
        }
        if (in_array($row['name'], ['admin', 'user'], true)) {
            Auth::flash('error', 'Cannot delete default roles');
            View::redirect('/roles');
        }
        if ((int) $row['c'] > 0) {
            Auth::flash('error', 'Cannot delete role with users');
            View::redirect('/roles');
        }
        $pdo->prepare('DELETE FROM roles WHERE id = ?')->execute([$id]);
        Auth::flash('success', 'Role deleted');
        View::redirect('/roles');
    }

    public function storeUser(): void
    {
        Auth::requireAdmin();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Auth::flash('error', 'Invalid CSRF');
            View::redirect('/roles');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $roleName = strtolower(trim((string) ($_POST['role'] ?? 'user')));

        if ($name === '' || $email === '' || $password === '') {
            Auth::flash('error', 'Nama, email, dan password wajib diisi');
            View::redirect('/roles');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Auth::flash('error', 'Format email tidak valid');
            View::redirect('/roles');
        }
        if (strlen($password) < 6) {
            Auth::flash('error', 'Password minimal 6 karakter');
            View::redirect('/roles');
        }
        if (!in_array($roleName, ['admin', 'user'], true)) {
            Auth::flash('error', 'Role harus admin atau user');
            View::redirect('/roles');
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            Auth::flash('error', 'Email sudah terdaftar');
            View::redirect('/roles');
        }

        $roleStmt = $pdo->prepare('SELECT id FROM roles WHERE name = ? LIMIT 1');
        $roleStmt->execute([$roleName]);
        $roleId = $roleStmt->fetchColumn();
        if (!$roleId) {
            Auth::flash('error', "Role '{$roleName}' belum tersedia");
            View::redirect('/roles');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare('INSERT INTO users (id, name, email, password, role_id) VALUES (?,?,?,?,?)')
            ->execute([Database::uuid(), $name, $email, $hash, $roleId]);

        Auth::flash('success', 'User baru berhasil dibuat');
        View::redirect('/roles');
    }

    public function deleteUser(): void
    {
        Auth::requireAdmin();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            View::redirect('/roles');
        }
        $id = (string) ($_POST['id'] ?? '');
        $currentUser = Auth::user();
        if ($id === ($currentUser['id'] ?? '')) {
            Auth::flash('error', 'Tidak bisa menghapus akun sendiri');
            View::redirect('/roles');
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetchColumn()) {
            Auth::flash('error', 'User tidak ditemukan');
            View::redirect('/roles');
        }
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        Auth::flash('success', 'User berhasil dihapus');
        View::redirect('/roles');
    }
}
