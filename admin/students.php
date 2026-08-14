<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('admin');

$currentPage = 'students';
$flash = get_flash();

$courses = $pdo->query("SELECT id, code, name FROM courses ORDER BY name ASC")->fetchAll();

$stmt = $pdo->query("
    SELECT u.id, u.first_name, u.last_name, u.email, u.status, u.created_at,
           s.student_id_number, s.year_level, s.section_label, s.is_mayor, c.code AS course_code,
           (SELECT COUNT(*) FROM student_warnings WHERE student_id = u.id) AS warning_count
    FROM users u
    JOIN students s ON s.user_id = u.id
    LEFT JOIN courses c ON c.id = s.course_id
    WHERE u.role = 'student'
    ORDER BY u.created_at DESC
");
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - CDSGA HUB Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-page">

    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="admin-content">
        <h1>Manage Students</h1>

        <?php if ($flash): ?>
            <div class="admin-card" style="border-color:<?= $flash['type'] === 'error' ? '#ff4757' : '#46ff82' ?>;">
                <?= sanitize($flash['message']) ?>
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <input type="text" id="studentSearchInput" class="admin-search-input" placeholder="Search by name or student ID...">

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Year &amp; Section</th>
                        <th>Warnings</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody">
                    <?php foreach ($students as $s): ?>
                        <tr data-search="<?= sanitize(strtolower($s['first_name'] . ' ' . $s['last_name'] . ' ' . $s['student_id_number'])) ?>">
                            <td><?= sanitize($s['student_id_number']) ?></td>
                            <td>
                                <?= sanitize($s['first_name'] . ' ' . $s['last_name']) ?>
                                <?php if ($s['is_mayor']): ?><span class="admin-mayor-badge">Mayor</span><?php endif; ?>
                            </td>
                            <td><?= sanitize($s['email']) ?></td>
                            <td><?= sanitize($s['course_code'] ?? '—') ?></td>
                            <td><?= (int)$s['year_level'] ?>-<?= sanitize($s['section_label']) ?></td>
                            <td>
                                <span class="admin-warning-badge <?= $s['warning_count'] >= 3 ? 'restricted' : '' ?>"><?= (int)$s['warning_count'] ?> / 3</span>
                            </td>
                            <td>
                                <span class="admin-status-badge status-<?= $s['status'] ?>"><?= ucfirst($s['status']) ?></span>
                            </td>
                            <td class="admin-table-actions">
                                <form action="process/toggle_student_status.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $s['status'] === 'disabled' ? 'active' : 'disabled' ?>">
                                    <button type="submit" class="admin-mini-btn <?= $s['status'] === 'disabled' ? 'enable' : 'disable' ?>">
                                        <?= $s['status'] === 'disabled' ? 'Enable' : 'Disable' ?>
                                    </button>
                                </form>
                                <?php if ($s['warning_count'] > 0): ?>
                                <form action="process/reset_student_warnings.php" method="POST" style="display:inline;" onsubmit="return confirm('Reset all warnings for this student?');">
                                    <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="admin-mini-btn">Reset Warnings</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="8" style="text-align:center; color:var(--admin-muted); padding:30px;">No students yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.getElementById('studentSearchInput').addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('#studentsTableBody tr[data-search]').forEach(function (row) {
                row.style.display = row.dataset.search.includes(q) ? '' : 'none';
            });
        });
    </script>
</body>
</html>