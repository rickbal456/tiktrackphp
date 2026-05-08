<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            View::redirect('/dashboard');
        }
        View::authPage('auth.login', ['title' => 'Sign in']);
    }

    public function login(): void
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Auth::flash('error', 'Invalid session. Try again.');
            View::redirect('/login');
        }
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $pdo = Database::pdo();
        $stmt = $pdo->prepare(
            'SELECT u.id, u.name, u.email, u.password, r.name AS role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.email = ?'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($password, (string) $row['password'])) {
            Auth::flash('error', 'Invalid email or password');
            View::redirect('/login');
        }
        Auth::login([
            'id' => (string) $row['id'],
            'name' => (string) $row['name'],
            'email' => (string) $row['email'],
            'role' => (string) $row['role'],
        ]);
        Auth::flash('success', 'Login successful');
        View::redirect('/dashboard');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            View::redirect('/dashboard');
        }
        View::authPage('auth.register', ['title' => 'Register']);
    }

    public function register(): void
    {
        if (!Csrf::verify($_POST['_csrf'] ?? null)) {
            Auth::flash('error', 'Invalid session.');
            View::redirect('/register');
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        if ($name === '' || $email === '' || $password === '') {
            Auth::flash('error', 'All fields are required');
            View::redirect('/register');
        }
        if ($password !== $confirm) {
            Auth::flash('error', 'Passwords do not match');
            View::redirect('/register');
        }
        if (strlen($password) < 6) {
            Auth::flash('error', 'Password min 6 characters');
            View::redirect('/register');
        }
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn()) {
            Auth::flash('error', 'Email already registered');
            View::redirect('/register');
        }
        $stmt = $pdo->prepare('SELECT id FROM roles WHERE name = ?');
        $stmt->execute(['user']);
        $roleId = $stmt->fetchColumn();
        if (!$roleId) {
            $roleId = Database::uuid();
            $pdo->prepare('INSERT INTO roles (id, name, description) VALUES (?,?,?)')
                ->execute([$roleId, 'user', 'Regular user']);
        }
        $uid = Database::uuid();
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo->prepare('INSERT INTO users (id, name, email, password, role_id) VALUES (?,?,?,?,?)')
            ->execute([$uid, $name, $email, $hash, $roleId]);
        Auth::flash('success', 'Registration successful! Please sign in.');
        View::redirect('/login');
    }

    public function logout(): void
    {
        Auth::start();
        Auth::logout();
        View::redirect('/login');
    }
}
