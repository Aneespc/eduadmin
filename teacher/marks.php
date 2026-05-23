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

$subjectId = isset($_GET['subject_id']) ? (int) $_GET['subject_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subjectId = (int) ($_POST['subject_id'] ?? 0);
    $enrollmentId = (int) ($_POST['enrollment_id'] ?? 0);
    $marksValue = trim((string) ($_POST['marks'] ?? ''));
    $attendanceValue = trim((string) ($_POST['attendance_percentage'] ?? ''));

    $validationStmt = db()->prepare(
        'SELECT e.enrollment_id
         FROM subjects s
         INNER JOIN enrollments e ON e.course_id = s.course_id
         WHERE s.subject_id = :subject_id
           AND s.teacher_id = :teacher_id
           AND e.enrollment_id = :enrollment_id
         LIMIT 1'
    );
    $validationStmt->execute([
        'subject_id' => $subjectId,
        'teacher_id' => $teacher['teacher_id'],
        'enrollment_id' => $enrollmentId,
    ]);

    if (!$validationStmt->fetch()) {
        flash_set('flash_error', 'That student is not enrolled in the selected subject course.');
        redirect('/teacher/marks.php?subject_id=' . $subjectId);
    }

    db()->beginTransaction();

    try {
        $existingMarkStmt = db()->prepare(
            'SELECT mark_id FROM marks WHERE enrollment_id = :enrollment_id AND subject_id = :subject_id LIMIT 1'
        );
        $existingAttendanceStmt = db()->prepare(
            'SELECT attendance_id FROM attendance WHERE enrollment_id = :enrollment_id AND subject_id = :subject_id LIMIT 1'
        );
        $existingMarkStmt->execute([
            'enrollment_id' => $enrollmentId,
            'subject_id' => $subjectId,
        ]);
        $existingAttendanceStmt->execute([
            'enrollment_id' => $enrollmentId,
            'subject_id' => $subjectId,
        ]);

        $markRow = $existingMarkStmt->fetch();
        $attendanceRow = $existingAttendanceStmt->fetch();

        if ($markRow) {
            $markStmt = db()->prepare('UPDATE marks SET marks = :marks WHERE mark_id = :mark_id');
            $markStmt->execute([
                'marks' => $marksValue === '' ? 0 : (int) $marksValue,
                'mark_id' => $markRow['mark_id'],
            ]);
        } else {
            $markStmt = db()->prepare(
                'INSERT INTO marks (enrollment_id, subject_id, marks)
                 VALUES (:enrollment_id, :subject_id, :marks)'
            );
            $markStmt->execute([
                'enrollment_id' => $enrollmentId,
                'subject_id' => $subjectId,
                'marks' => $marksValue === '' ? 0 : (int) $marksValue,
            ]);
        }

        if ($attendanceRow) {
            $attendanceStmt = db()->prepare(
                'UPDATE attendance SET attendance_percentage = :attendance_percentage WHERE attendance_id = :attendance_id'
            );
            $attendanceStmt->execute([
                'attendance_percentage' => $attendanceValue === '' ? 0 : (float) $attendanceValue,
                'attendance_id' => $attendanceRow['attendance_id'],
            ]);
        } else {
            $attendanceStmt = db()->prepare(
                'INSERT INTO attendance (enrollment_id, subject_id, attendance_percentage)
                 VALUES (:enrollment_id, :subject_id, :attendance_percentage)'
            );
            $attendanceStmt->execute([
                'enrollment_id' => $enrollmentId,
                'subject_id' => $subjectId,
                'attendance_percentage' => $attendanceValue === '' ? 0 : (float) $attendanceValue,
            ]);
        }

        db()->commit();
        flash_set('flash_success', 'Marks and attendance updated.');
    } catch (Throwable $e) {
        db()->rollBack();
        flash_set('flash_error', 'Unable to save the record.');
    }

    redirect('/teacher/marks.php?subject_id=' . $subjectId);
}

$subjectListStmt = db()->prepare(
    'SELECT s.subject_id, s.subject_name, c.course_name
     FROM subjects s
     INNER JOIN courses c ON c.course_id = s.course_id
     WHERE s.teacher_id = :teacher_id
     ORDER BY c.course_name, s.subject_name'
);
$subjectListStmt->execute(['teacher_id' => $teacher['teacher_id']]);
$subjectOptions = $subjectListStmt->fetchAll();

$studentRows = [];
$selectedSubject = null;

if ($subjectId > 0) {
    $subjectInfoStmt = db()->prepare(
        'SELECT s.subject_id, s.subject_name, c.course_name
         FROM subjects s
         INNER JOIN courses c ON c.course_id = s.course_id
         WHERE s.subject_id = :subject_id AND s.teacher_id = :teacher_id
         LIMIT 1'
    );
    $subjectInfoStmt->execute([
        'subject_id' => $subjectId,
        'teacher_id' => $teacher['teacher_id'],
    ]);
    $selectedSubject = $subjectInfoStmt->fetch();

    if ($selectedSubject) {
        $studentStmt = db()->prepare(
            'SELECT
                e.enrollment_id,
                u.username AS student_name,
                u.email,
                COALESCE(m.marks, 0) AS marks,
                COALESCE(a.attendance_percentage, 0) AS attendance_percentage
             FROM enrollments e
             INNER JOIN students st ON st.student_id = e.student_id
             INNER JOIN users u ON u.user_id = st.user_id
             INNER JOIN subjects s ON s.course_id = e.course_id
             LEFT JOIN marks m ON m.enrollment_id = e.enrollment_id AND m.subject_id = s.subject_id
             LEFT JOIN attendance a ON a.enrollment_id = e.enrollment_id AND a.subject_id = s.subject_id
             WHERE s.subject_id = :subject_id AND s.teacher_id = :teacher_id
             ORDER BY u.username'
        );
        $studentStmt->execute([
            'subject_id' => $subjectId,
            'teacher_id' => $teacher['teacher_id'],
        ]);
        $studentRows = $studentStmt->fetchAll();
    }
}

$error = flash_get('flash_error');
$success = flash_get('flash_success');

page_start('Manage Marks');
?>
<section class="panel stack">
    <div>
        <h2 class="section-title">Marks and attendance</h2>
        <p class="muted">Only students enrolled in the course behind a subject are available here.</p>
    </div>
    <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="get">
        <label>
            Subject
            <select name="subject_id" onchange="this.form.submit()">
                <option value="">Select a subject</option>
                <?php foreach ($subjectOptions as $subject): ?>
                    <option value="<?= (int) $subject['subject_id'] ?>" <?= $subjectId === (int) $subject['subject_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($subject['course_name'] . ' - ' . $subject['subject_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>
</section>

<?php if ($selectedSubject): ?>
    <section class="panel">
        <h2 class="section-title"><?= htmlspecialchars($selectedSubject['course_name'] . ' - ' . $selectedSubject['subject_name']) ?></h2>
        <table>
            <thead>
            <tr>
                <th>Student</th>
                <th>Email</th>
                <th>Marks</th>
                <th>Attendance %</th>
                <th>Save</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$studentRows): ?>
                <tr><td colspan="5">No students are enrolled in this course yet.</td></tr>
            <?php else: ?>
                <?php foreach ($studentRows as $row): ?>
                    <?php $formId = 'student-record-' . (int) $row['enrollment_id']; ?>
                    <tr>
                        <td><?= htmlspecialchars($row['student_name']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td>
                            <input form="<?= htmlspecialchars($formId) ?>" type="number" name="marks" min="0" max="100" value="<?= htmlspecialchars((string) $row['marks']) ?>">
                        </td>
                        <td>
                            <input form="<?= htmlspecialchars($formId) ?>" type="number" name="attendance_percentage" min="0" max="100" step="0.01" value="<?= htmlspecialchars((string) $row['attendance_percentage']) ?>">
                        </td>
                        <td>
                            <form id="<?= htmlspecialchars($formId) ?>" action="<?= htmlspecialchars(url('/teacher/marks.php')) ?>" method="post">
                                <input type="hidden" name="subject_id" value="<?= (int) $subjectId ?>">
                                <input type="hidden" name="enrollment_id" value="<?= (int) $row['enrollment_id'] ?>">
                                <button type="submit">Save</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </section>
<?php endif; ?>
<?php
page_end();
