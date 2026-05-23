<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';

$user = require_login('teacher');

$teacherStmt = db()->prepare('SELECT teacher_id FROM teachers WHERE user_id = :user_id LIMIT 1');
$teacherStmt->execute(['user_id' => $user['user_id']]);
$teacher = $teacherStmt->fetch();

if (!$teacher) {
    flash_set('flash_error', 'Teacher profile was not found for this account.');
    redirect('/index.php');
}

$subjectStmt = db()->prepare(
    'SELECT s.subject_id, s.subject_name, c.course_name,
            COUNT(DISTINCT e.enrollment_id) AS enrolled_students
     FROM subjects s
     INNER JOIN courses c ON c.course_id = s.course_id
     LEFT JOIN enrollments e ON e.course_id = s.course_id
     WHERE s.teacher_id = :teacher_id
     GROUP BY s.subject_id, s.subject_name, c.course_name
     ORDER BY c.course_name, s.subject_name'
);
$subjectStmt->execute(['teacher_id' => $teacher['teacher_id']]);
$subjects = $subjectStmt->fetchAll();

$statsStmt = db()->prepare(
    'SELECT
        COUNT(*) AS subject_count,
        COUNT(DISTINCT e.student_id) AS student_count
     FROM subjects s
     LEFT JOIN enrollments e ON e.course_id = s.course_id
     WHERE s.teacher_id = :teacher_id'
);
$statsStmt->execute(['teacher_id' => $teacher['teacher_id']]);
$stats = $statsStmt->fetch() ?: [];

page_start('Teacher Dashboard');
?>
<section class="grid-2">
    <div class="metric"><div class="value"><?= (int) ($stats['subject_count'] ?? 0) ?></div><div>Assigned subjects</div></div>
    <div class="metric"><div class="value"><?= (int) ($stats['student_count'] ?? 0) ?></div><div>Students across courses</div></div>
</section>

<section class="panel">
    <h2 class="section-title">My subjects</h2>
    <table>
        <thead>
        <tr>
            <th>Course</th>
            <th>Subject</th>
            <th>Enrolled students</th>
            <th>Update records</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$subjects): ?>
            <tr><td colspan="4">No subjects are assigned to this teacher yet.</td></tr>
        <?php else: ?>
            <?php foreach ($subjects as $subject): ?>
                <tr>
                    <td><?= htmlspecialchars($subject['course_name']) ?></td>
                    <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                    <td><?= (int) $subject['enrolled_students'] ?></td>
                    <td><a href="<?= htmlspecialchars(url('/teacher/marks.php')) ?>?subject_id=<?= (int) $subject['subject_id'] ?>">Manage marks and attendance</a></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php
page_end();
