<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('admin');

$currentPage = 'sections';
$flash = get_flash();

$courses = $pdo->query("SELECT id, code, name FROM courses ORDER BY name ASC")->fetchAll();
$professors = $pdo->query("
    SELECT u.id, u.first_name, u.last_name, p.department
    FROM users u JOIN professors p ON p.user_id = u.id
    WHERE u.role = 'professor'
    ORDER BY u.first_name ASC
")->fetchAll();

$sections = $pdo->query("
    SELECT cs.id, cs.subject_name, cs.year_level, cs.section_label, cs.semester_label,
           c.code AS course_code, u.first_name, u.last_name
    FROM class_sections cs
    JOIN courses c ON c.id = cs.course_id
    JOIN users u ON u.id = cs.professor_id
    ORDER BY cs.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sections - CDSGA HUB Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-page">

    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="admin-content">
        <h1>Manage Sections</h1>

        <?php if ($flash): ?>
            <div class="admin-card" style="border-color:<?= $flash['type'] === 'error' ? '#ff4757' : '#46ff82' ?>;">
                <?= sanitize($flash['message']) ?>
            </div>
        <?php endif; ?>

        <div class="admin-card">
            <h2 style="color:#fff; font-size:16px; margin-bottom:16px;">Assign Section to Professor</h2>
            <form action="process/assign_section.php" method="POST">
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label for="professor_id">Professor</label>
                        <select id="professor_id" name="professor_id" required>
                            <option value="">Select professor</option>
                            <?php foreach ($professors as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= sanitize($p['first_name'] . ' ' . $p['last_name']) ?> (<?= sanitize($p['department']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="admin-form-group">
                        <label for="course_id">Course</label>
                        <select id="course_id" name="course_id" required>
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= sanitize($c['name']) ?> (<?= sanitize($c['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label for="subject_name">Subject Name</label>
                        <input type="text" id="subject_name" name="subject_name" placeholder="e.g. Web Development" required maxlength="150">
                    </div>
                    <div class="admin-form-group">
                        <label for="year_level">Year Level</label>
                        <select id="year_level" name="year_level" required>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                </div>
                <div class="admin-form-row">
                    <div class="admin-form-group">
                        <label for="section_label">Section Label</label>
                        <input type="text" id="section_label" name="section_label" placeholder="e.g. 2-3" required maxlength="10">
                    </div>
                    <div class="admin-form-group">
                        <label for="semester_label">Semester</label>
                        <input type="text" id="semester_label" name="semester_label" placeholder="e.g. 1st Semester 2025-2026" required maxlength="50">
                    </div>
                </div>
                <button type="submit" class="admin-submit-btn">Assign Section</button>
            </form>
        </div>

        <div class="admin-card">
            <input type="text" id="sectionSearchInput" class="admin-search-input" placeholder="Search by professor or subject...">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Professor</th>
                        <th>Subject</th>
                        <th>Course / Section</th>
                        <th>Semester</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="sectionsTableBody">
                    <?php foreach ($sections as $s): ?>
                        <tr data-search="<?= sanitize(strtolower($s['first_name'] . ' ' . $s['last_name'] . ' ' . $s['subject_name'])) ?>">
                            <td><?= sanitize($s['first_name'] . ' ' . $s['last_name']) ?></td>
                            <td><?= sanitize($s['subject_name']) ?></td>
                            <td><?= sanitize($s['course_code']) ?> <?= (int)$s['year_level'] ?>-<?= sanitize($s['section_label']) ?></td>
                            <td><?= sanitize($s['semester_label']) ?></td>
                            <td>
                                <form action="process/delete_section.php" method="POST" onsubmit="return confirm('Remove this section assignment?');">
                                    <input type="hidden" name="section_id" value="<?= $s['id'] ?>">
                                    <button type="submit" class="admin-mini-btn disable">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($sections)): ?>
                        <tr><td colspan="5" style="text-align:center; color:var(--admin-muted); padding:30px;">No sections assigned yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.getElementById('sectionSearchInput').addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            document.querySelectorAll('#sectionsTableBody tr[data-search]').forEach(function (row) {
                row.style.display = row.dataset.search.includes(q) ? '' : 'none';
            });
        });
    </script>
</body>
</html>