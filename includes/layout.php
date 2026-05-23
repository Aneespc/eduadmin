<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function page_start(string $title): void
{
    $user = current_user();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($title) ?> | <?= htmlspecialchars(APP_NAME) ?></title>
        <link rel="stylesheet" href="<?= htmlspecialchars(url('/assets/style.css')) ?>">
    </head>
    <body>
    <div class="shell">
        <header class="topbar">
            <div>
                <a class="brand" href="<?= htmlspecialchars(url('/index.php')) ?>"><?= htmlspecialchars(APP_NAME) ?></a>
                <?php if ($user): ?>
                    <p class="subtitle">Signed in as <?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['role']) ?>)</p>
                <?php else: ?>
                    <p class="subtitle">Role-based student management portal</p>
                <?php endif; ?>
            </div>
            <?php if ($user): ?>
                <nav class="nav">
                    <a href="<?= htmlspecialchars(url(dashboard_path($user['role']))) ?>">Dashboard</a>
                    <?php if ($user['role'] === 'admin'): ?>
                        <a href="<?= htmlspecialchars(url('/admin/users.php')) ?>">Users</a>
                        <a href="<?= htmlspecialchars(url('/admin/courses.php')) ?>">Courses</a>
                        <a href="<?= htmlspecialchars(url('/admin/subjects.php')) ?>">Subjects</a>
                        <a href="<?= htmlspecialchars(url('/admin/enrollments.php')) ?>">Enrollments</a>
                    <?php elseif ($user['role'] === 'teacher'): ?>
                        <a href="<?= htmlspecialchars(url('/teacher/marks.php')) ?>">Marks & Attendance</a>
                    <?php endif; ?>
                    <a href="<?= htmlspecialchars(url('/logout.php')) ?>">Logout</a>
                </nav>
            <?php endif; ?>
        </header>
        <main class="content">
    <?php
}

function page_end(): void
{
    ?>
        </main>
    </div>
    </body>
    </html>
    <?php
}
