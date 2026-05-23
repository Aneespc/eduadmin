<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';

require_login('admin');

$counts = [
    'users' => (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'students' => (int) db()->query('SELECT COUNT(*) FROM students')->fetchColumn(),
    'teachers' => (int) db()->query('SELECT COUNT(*) FROM teachers')->fetchColumn(),
    'courses' => (int) db()->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
    'subjects' => (int) db()->query('SELECT COUNT(*) FROM subjects')->fetchColumn(),
    'enrollments' => (int) db()->query('SELECT COUNT(*) FROM enrollments')->fetchColumn(),
];

page_start('Admin Dashboard');
?>
<section class="grid-3">
    <div class="metric"><div class="value"><?= $counts['users'] ?></div><div>Total users</div></div>
    <div class="metric"><div class="value"><?= $counts['students'] ?></div><div>Students</div></div>
    <div class="metric"><div class="value"><?= $counts['teachers'] ?></div><div>Teachers</div></div>
    <div class="metric"><div class="value"><?= $counts['courses'] ?></div><div>Courses</div></div>
    <div class="metric"><div class="value"><?= $counts['subjects'] ?></div><div>Subjects</div></div>
    <div class="metric"><div class="value"><?= $counts['enrollments'] ?></div><div>Enrollments</div></div>
</section>

<section class="cards">
    <article class="card">
        <h3>Manage users</h3>
        <p class="muted">Create student, teacher, and admin accounts.</p>
        <a href="<?= htmlspecialchars(url('/admin/users.php')) ?>">Open users</a>
    </article>
    <article class="card">
        <h3>Manage courses</h3>
        <p class="muted">Create courses and review the catalog.</p>
        <a href="<?= htmlspecialchars(url('/admin/courses.php')) ?>">Open courses</a>
    </article>
    <article class="card">
        <h3>Manage subjects</h3>
        <p class="muted">Attach subjects to courses and teachers.</p>
        <a href="<?= htmlspecialchars(url('/admin/subjects.php')) ?>">Open subjects</a>
    </article>
    <article class="card">
        <h3>Manage enrollments</h3>
        <p class="muted">Enroll students into one or more courses.</p>
        <a href="<?= htmlspecialchars(url('/admin/enrollments.php')) ?>">Open enrollments</a>
    </article>
</section>
<?php
page_end();
