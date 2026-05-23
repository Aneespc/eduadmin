<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function url(string $path = ''): string
{
    $configuredBase = trim(BASE_URL);
    $detectedBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/.');
    $base = $configuredBase !== '' ? rtrim($configuredBase, '/') : ($detectedBase === '/' ? '' : $detectedBase);
    $path = '/' . ltrim($path, '/');

    if ($base === '') {
        return $path;
    }

    return $base . $path;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'user_id' => (int) $user['user_id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_login(?string $role = null): array
{
    $user = current_user();

    if ($user === null) {
        $_SESSION['flash_error'] = 'Please log in first.';
        redirect('/index.php');
    }

    if ($role !== null && $user['role'] !== $role) {
        $_SESSION['flash_error'] = 'You do not have permission to access that page.';
        redirect('/dashboard.php');
    }

    return $user;
}

function dashboard_path(string $role): string
{
    return match ($role) {
        'admin' => '/admin/dashboard.php',
        'teacher' => '/teacher/dashboard.php',
        default => '/student/dashboard.php',
    };
}

function flash_set(string $key, string $message): void
{
    $_SESSION[$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION[$key])) {
        return null;
    }

    $message = $_SESSION[$key];
    unset($_SESSION[$key]);

    return $message;
}
