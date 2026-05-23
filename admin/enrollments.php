<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';

require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = (int) ($_POST['student_id'] ?? 0);
    $courseId = (int) ($_POST['course_id'] ?? 0);

    if ($studentId <= 0 || $courseId <= 0) {
        flash_set('flash_error', 'Student and course are required.');
        redirect('/admin/enrollments.php');
    }

    try {
        $stmt = db()->prepare(
            'INSERT INTO enrollments (student_id, course_id)
             VALUES (:student_id, :course_id)'
        );
        $stmt->execute([
            'student_id' => $studentId,
            'course_id' => $courseId,
        ]);
        flash_set('flash_success', 'Enrollment created.');
    } catch (Throwable $e) {
        flash_set('flash_error', 'Unable to create enrollment. The student may already be enrolled in that course.');
    }

    redirect('/admin/enrollments.php');
}

$students = db()->query(
    'SELECT s.student_id, u.username
     FROM students s
     INNER JOIN users u ON u.user_id = s.user_id
     ORDER BY u.username'
)->fetchAll();
$courses = db()->query('SELECT course_id, course_name FROM courses ORDER BY course_name')->fetchAll();
$enrollments = db()->query(
    'SELECT e.enrollment_id, u.username AS student_name, c.course_name
     FROM enrollments e
     INNER JOIN students s ON s.student_id = e.student_id
     INNER JOIN users u ON u.user_id = s.user_id
     INNER JOIN courses c ON c.course_id = e.course_id
     ORDER BY u.username, c.course_name'
)->fetchAll();

$error = flash_get('flash_error');
$success = flash_get('flash_success');

page_start('Manage Enrollments');
?>
<section class="grid-2">
    <div class="panel">
        <h2 class="section-title">Enroll student in course</h2>
        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <form method="post">
            <label>
                Student
                <select name="student_id" required>
                    <option value="">Select student</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= (int) $student['student_id'] ?>"><?= htmlspecialchars($student['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Course
                <select name="course_id" required>
                    <option value="">Select course</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= (int) $course['course_id'] ?>"><?= htmlspecialchars($course['course_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Create enrollment</button>
        </form>
    </div>
    <div class="panel">
        <h2 class="section-title">Enrollment list</h2>
        <table>
            <thead><tr><th>ID</th><th>Student</th><th>Course</th></tr></thead>
            <tbody>
            <?php foreach ($enrollments as $enrollment): ?>
                <tr>
                    <td><?= (int) $enrollment['enrollment_id'] ?></td>
                    <td><?= htmlspecialchars($enrollment['student_name']) ?></td>
                    <td><?= htmlspecialchars($enrollment['course_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
page_end();
