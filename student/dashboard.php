<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/layout.php';

$user = require_login('student');

$studentStmt = db()->prepare('SELECT student_id FROM students WHERE user_id = :user_id LIMIT 1');
$studentStmt->execute(['user_id' => $user['user_id']]);
$student = $studentStmt->fetch();

if (!$student) {
    flash_set('flash_error', 'Student profile was not found for this account.');
    redirect('/index.php');
}

$summaryStmt = db()->prepare(
    'SELECT
        COUNT(DISTINCT e.course_id) AS course_count,
        COUNT(DISTINCT s.subject_id) AS subject_count,
        ROUND(AVG(m.marks), 2) AS average_marks,
        ROUND(AVG(a.attendance_percentage), 2) AS average_attendance
     FROM enrollments e
     LEFT JOIN subjects s ON s.course_id = e.course_id
     LEFT JOIN marks m ON m.enrollment_id = e.enrollment_id AND m.subject_id = s.subject_id
     LEFT JOIN attendance a ON a.enrollment_id = e.enrollment_id AND a.subject_id = s.subject_id
     WHERE e.student_id = :student_id'
);
$summaryStmt->execute(['student_id' => $student['student_id']]);
$summary = $summaryStmt->fetch() ?: [];

$subjectsStmt = db()->prepare(
    'SELECT
        c.course_name,
        s.subject_name,
        tu.username AS teacher_name,
        COALESCE(m.marks, 0) AS marks,
        COALESCE(a.attendance_percentage, 0) AS attendance_percentage
     FROM enrollments e
     INNER JOIN courses c ON c.course_id = e.course_id
     INNER JOIN subjects s ON s.course_id = c.course_id
     LEFT JOIN teachers t ON t.teacher_id = s.teacher_id
     LEFT JOIN users tu ON tu.user_id = t.user_id
     LEFT JOIN marks m ON m.enrollment_id = e.enrollment_id AND m.subject_id = s.subject_id
     LEFT JOIN attendance a ON a.enrollment_id = e.enrollment_id AND a.subject_id = s.subject_id
     WHERE e.student_id = :student_id
     ORDER BY c.course_name, s.subject_name'
);
$subjectsStmt->execute(['student_id' => $student['student_id']]);
$subjects = $subjectsStmt->fetchAll();

page_start('Student Dashboard');
?>
<section class="grid-3">
    <div class="metric"><div class="value"><?= (int) ($summary['course_count'] ?? 0) ?></div><div>Courses</div></div>
    <div class="metric"><div class="value"><?= (int) ($summary['subject_count'] ?? 0) ?></div><div>Subjects</div></div>
    <div class="metric"><div class="value"><?= htmlspecialchars((string) ($summary['average_attendance'] ?? '0')) ?>%</div><div>Average attendance</div></div>
</section>

<section class="panel">
    <h2 class="section-title">My academic record</h2>
    <table>
        <thead>
        <tr>
            <th>Course</th>
            <th>Subject</th>
            <th>Teacher</th>
            <th>Marks</th>
            <th>Attendance %</th>
        </tr>
        </thead>
        <tbody>
        <?php if (!$subjects): ?>
            <tr><td colspan="5">No enrollment data found yet.</td></tr>
        <?php else: ?>
            <?php foreach ($subjects as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['course_name']) ?></td>
                    <td><?= htmlspecialchars($row['subject_name']) ?></td>
                    <td><?= htmlspecialchars($row['teacher_name'] ?? 'Not assigned') ?></td>
                    <td><?= htmlspecialchars((string) $row['marks']) ?></td>
                    <td><?= htmlspecialchars((string) $row['attendance_percentage']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</section>
<?php
page_end();
