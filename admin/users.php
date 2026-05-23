<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';

require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($username === '' || $email === '' || $password === '' || !in_array($role, ['student', 'teacher', 'admin'], true)) {
        flash_set('flash_error', 'Every user field is required.');
        redirect('/admin/users.php');
    }

    db()->beginTransaction();

    try {
        $userStmt = db()->prepare(
            'INSERT INTO users (username, email, password, role)
             VALUES (:username, :email, :password, :role)'
        );
        $userStmt->execute([
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
        ]);

        $userId = (int) db()->lastInsertId();

        if ($role === 'student') {
            $studentStmt = db()->prepare('INSERT INTO students (user_id) VALUES (:user_id)');
            $studentStmt->execute(['user_id' => $userId]);
        } elseif ($role === 'teacher') {
            $teacherStmt = db()->prepare('INSERT INTO teachers (user_id) VALUES (:user_id)');
            $teacherStmt->execute(['user_id' => $userId]);
        }

        db()->commit();
        flash_set('flash_success', 'User created successfully.');
    } catch (Throwable $e) {
        db()->rollBack();
        flash_set('flash_error', 'Unable to create user. Check for duplicate email or broken schema constraints.');
    }

    redirect('/admin/users.php');
}

$users = db()->query(
    'SELECT u.user_id, u.username, u.email, u.role,
            s.student_id, t.teacher_id
     FROM users u
     LEFT JOIN students s ON s.user_id = u.user_id
     LEFT JOIN teachers t ON t.user_id = u.user_id
     ORDER BY u.user_id DESC'
)->fetchAll();

$error = flash_get('flash_error');
$success = flash_get('flash_success');

page_start('Manage Users');
?>
<section class="grid-2">
    <div class="panel">
        <h2 class="section-title">Create user</h2>
        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <form method="post">
            <label>Username<input type="text" name="username" required></label>
            <label>Email<input type="email" name="email" required></label>
            <label>Password<input type="password" name="password" required></label>
            <label>
                Role
                <select name="role" required>
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                    <option value="admin">Admin</option>
                </select>
            </label>
            <button type="submit">Create user</button>
        </form>
    </div>

    <div class="panel">
        <h2 class="section-title">Current users</h2>
        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $item): ?>
                <tr>
                    <td><?= (int) $item['user_id'] ?></td>
                    <td><?= htmlspecialchars($item['username']) ?></td>
                    <td><?= htmlspecialchars($item['email']) ?></td>
                    <td><?= htmlspecialchars($item['role']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
page_end();
