<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';

require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjectName = trim($_POST['subject_name'] ?? '');
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $teacherId = (int) ($_POST['teacher_id'] ?? 0);

    if ($subjectName === '' || $courseId <= 0 || $teacherId <= 0) {
        flash_set('flash_error', 'Subject name, course, and teacher are required.');
        redirect('/admin/subjects.php');
    }

    try {
        $stmt = db()->prepare(
            'INSERT INTO subjects (subject_name, course_id, teacher_id)
             VALUES (:subject_name, :course_id, :teacher_id)'
        );
        $stmt->execute([
            'subject_name' => $subjectName,
            'course_id' => $courseId,
            'teacher_id' => $teacherId,
        ]);
        flash_set('flash_success', 'Subject created.');
    } catch (Throwable $e) {
        flash_set('flash_error', 'Unable to create subject. Each teacher can only be assigned to one subject in your current schema.');
    }

    redirect('/admin/subjects.php');
}

$courses = db()->query('SELECT course_id, course_name FROM courses ORDER BY course_name')->fetchAll();
$teachers = db()->query(
    'SELECT t.teacher_id, u.username
     FROM teachers t
     INNER JOIN users u ON u.user_id = t.user_id
     ORDER BY u.username'
)->fetchAll();
$subjects = db()->query(
    'SELECT s.subject_id, s.subject_name, c.course_name, u.username AS teacher_name
     FROM subjects s
     INNER JOIN courses c ON c.course_id = s.course_id
     INNER JOIN teachers t ON t.teacher_id = s.teacher_id
     INNER JOIN users u ON u.user_id = t.user_id
     ORDER BY c.course_name, s.subject_name'
)->fetchAll();

$error = flash_get('flash_error');
$success = flash_get('flash_success');

page_start('Manage Subjects');
?>
<section class="grid-2">
    <div class="panel">
        <h2 class="section-title">Create subject</h2>
        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <form method="post">
            <label>Subject name<input type="text" name="subject_name" required></label>
            <label>
                Course
                <select name="course_id" required>
                    <option value="">Select course</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= (int) $course['course_id'] ?>"><?= htmlspecialchars($course['course_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Teacher
                <select name="teacher_id" required>
                    <option value="">Select teacher</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= (int) $teacher['teacher_id'] ?>"><?= htmlspecialchars($teacher['username']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Create subject</button>
        </form>
    </div>
    <div class="panel">
        <h2 class="section-title">Subject list</h2>
        <table>
            <thead><tr><th>ID</th><th>Subject</th><th>Course</th><th>Teacher</th></tr></thead>
            <tbody>
            <?php foreach ($subjects as $subject): ?>
                <tr>
                    <td><?= (int) $subject['subject_id'] ?></td>
                    <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                    <td><?= htmlspecialchars($subject['course_name']) ?></td>
                    <td><?= htmlspecialchars($subject['teacher_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php
page_end();
