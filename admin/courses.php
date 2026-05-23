<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';

require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseName = trim($_POST['course_name'] ?? '');

    if ($courseName === '') {
        flash_set('flash_error', 'Course name is required.');
        redirect('/admin/courses.php');
    }

    try {
        $stmt = db()->prepare('INSERT INTO courses (course_name) VALUES (:course_name)');
        $stmt->execute(['course_name' => $courseName]);
        flash_set('flash_success', 'Course added.');
    } catch (Throwable $e) {
        flash_set('flash_error', 'Unable to create course.');
    }

    redirect('/admin/courses.php');
}

$courses = db()->query(
    'SELECT c.course_id, c.course_name, COUNT(DISTINCT s.subject_id) AS subject_count
     FROM courses c
     LEFT JOIN subjects s ON s.course_id = c.course_id
     GROUP BY c.course_id, c.course_name
     ORDER BY c.course_name'
)->fetchAll();

$error = flash_get('flash_error');
$success = flash_get('flash_success');

page_start('Manage Courses');
?>
<section class="grid-2">
    <div class="panel">
        <h2 class="section-title">Create course</h2>
        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <form method="post">
            <label>Course name<input type="text" name="course_name" required></label>
            <button type="submit">Add course</button>
        </form>
    </div>
    <div class="panel">
        <h2 class="section-title">Course catalog</h2>
        <table>
            <thead><tr><th>ID</th><th>Course</th><th>Subjects</th></tr></thead>
            <tbody>
            <?php foreach ($courses as $course): ?>
                <tr>
                    <td><?= (int) $course['course_id'] ?></td>
                    <td><?= htmlspecialchars($course['course_name']) ?></td>
                    <td><?= (int) $course['subject_count'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
page_end();
