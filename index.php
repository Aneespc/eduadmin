<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

if (is_logged_in()) {
    redirect(dashboard_path(current_user()['role']));
}

$error = flash_get('flash_error');

page_start('Welcome');
?>
<section class="hero">
    <div class="panel soft stack">
        <div>
            <h1 class="section-title">A calm front door for students, teachers, and admins</h1>
            <p class="muted">Use one shared login page, choose your role, and step into the tools that fit your work.</p>
        </div>
        <div class="cards">
            <article class="card">
                <h3>Student</h3>
                <p class="muted">See enrolled courses, subjects, marks, and attendance.</p>
            </article>
            <article class="card">
                <h3>Teacher</h3>
                <p class="muted">Manage marks and attendance only for assigned subjects.</p>
            </article>
            <article class="card">
                <h3>Admin</h3>
                <p class="muted">Create users, courses, subjects, and student enrollments.</p>
            </article>
        </div>
    </div>
    <div class="panel stack">
        <div>
            <h2 class="section-title">Login</h2>
            <p class="muted">The role you choose must match the user record in the database.</p>
        </div>
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form action="<?= htmlspecialchars(url('/login.php')) ?>" method="post">
            <label>
                Email
                <input type="email" name="email" required>
            </label>
            <label>
                Password
                <input type="password" name="password" required>
            </label>
            <label>
                Login as
                <select name="role" required>
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                    <option value="admin">Admin</option>
                </select>
            </label>
            <button type="submit">Enter portal</button>
        </form>
    </div>
</section>
<?php
page_end();
