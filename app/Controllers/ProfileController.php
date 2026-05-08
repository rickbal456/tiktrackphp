<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;

final class ProfileController
{
    public function index(): void
    {
        Auth::requireAuth();
        View::dashboard('profile.index', ['title' => 'User Profile']);
    }

    public function update(): void
    {
        Auth::requireAuth();
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Auth::flash('error', 'Invalid CSRF');
            View::redirect('/profile');
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        if ($name === '' || $email === '') {
            Auth::flash('error', 'Name and email required');
            View::redirect('/profile');
        }
        if ($password !== '' && $password !== $confirm) {
            Auth::flash('error', 'Passwords do not match');
            View::redirect('/profile');
        }
        if ($password !== '' && strlen($password) < 6) {
            Auth::flash('error', 'Password min 6 characters');
            View::redirect('/profile');
        }
        $pdo = Database::pdo();
        $uid = Auth::user()['id'];
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $stmt->execute([$email, $uid]);
        if ($stmt->fetchColumn()) {
            Auth::flash('error', 'Email already in use');
            View::redirect('/profile');
        }
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?')->execute([$name, $email, $hash, $uid]);
        } else {
            $pdo->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?')->execute([$name, $email, $uid]);
        }
        $stmt = $pdo->prepare('SELECT u.id, u.name, u.email, r.name AS role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?');
        $stmt->execute([$uid]);
        $row = $stmt->fetch();
        if ($row) {
            Auth::login([
                'id' => (string) $row['id'],
                'name' => (string) $row['name'],
                'email' => (string) $row['email'],
                'role' => (string) $row['role'],
            ]);
        }
        Auth::flash('success', 'Profile updated');
        View::redirect('/profile');
    }
}
