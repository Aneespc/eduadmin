<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';

if ($email === '' || $password === '' || !in_array($role, ['student', 'teacher', 'admin'], true)) {
    flash_set('flash_error', 'Please fill in every login field.');
    redirect('/index.php');
}

$stmt = db()->prepare('SELECT user_id, username, email, password, role FROM users WHERE email = :email AND role = :role LIMIT 1');
$stmt->execute([
    'email' => $email,
    'role' => $role,
]);
$user = $stmt->fetch();

if (!$user) {
    flash_set('flash_error', 'Invalid credentials for the selected role.');
    redirect('/index.php');
}

if (password_verify($password, $user['password'])) {
    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
        $rehashStmt = db()->prepare('UPDATE users SET password = :password WHERE user_id = :user_id');
        $rehashStmt->execute([
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'user_id' => $user['user_id'],
        ]);
    }
} elseif (hash_equals((string) $user['password'], $password)) {
    $upgradeStmt = db()->prepare('UPDATE users SET password = :password WHERE user_id = :user_id');
    $upgradeStmt->execute([
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'user_id' => $user['user_id'],
    ]);
} else {
    flash_set('flash_error', 'Invalid credentials for the selected role.');
    redirect('/index.php');
}

login_user($user);
redirect(dashboard_path($user['role']));
