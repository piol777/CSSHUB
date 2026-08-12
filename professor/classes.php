<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('professor');

$currentPage = 'classes';
$courses = $pdo->query("SELECT id, code, name FROM courses ORDER BY name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Classes - CDSGA HUB</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=2">
    <link rel="stylesheet" href="../assets/css/classes.css?v=1">
</head>
<body class="dashboard-page">

    <?php include __DIR__ . '/../includes/professor_nav.php'; ?>

    <div class="classes-page-container">
        <div class="classes-page-header">
            <div>
                <h1>My Classes</h1>
                <p>Manage your class sections for this semester</p>
            </div>
            <button type="button" class="classes-add-btn" id="openCreateClassModal">+ Add Class</button>
        </div>

        <div class="classes-grid" id="classesGrid">
            <div class="classes-empty">Loading...</div>
        </div>
    </div>

    <div class="modal-overlay" id="createClassModal">
        <div class="modal-box modal-box-clean">
            <div class="modal-header modal-header-centered">
                <h2>Add Class</h2>
                <button type="button" class="modal-close-btn" id="closeCreateClassModal">&times;</button>
            </div>
            <form id="createClassForm" class="modal-form">
                <input type="text" id="classSubjectName" class="clean-field" placeholder="Subject Name" required maxlength="150">

                <select id="classCourse" class="clean-field" required>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= sanitize($c['name']) ?> (<?= sanitize($c['code']) ?>)</option>
                    <?php endforeach; ?>
                </select>

                <div class="clean-field-row">
                    <select id="classYearLevel" class="clean-field" required>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                    <input type="text" id="classSectionLabel" class="clean-field" placeholder="Section: Ex. 2-3" required maxlength="10">
                </div>

                <input type="text" id="classSemesterLabel" class="clean-field" placeholder="Semester: Ex. 1st Sem 2025-2026" required maxlength="50">

                <div class="clean-color-label">Color</div>
                <div class="color-swatch-row" id="colorSwatchRow">
                    <button type="button" class="color-swatch" style="background:#7c5cff" data-color="#7c5cff"></button>
                    <button type="button" class="color-swatch" style="background:#2952ff" data-color="#2952ff"></button>
                    <button type="button" class="color-swatch" style="background:#2f9e44" data-color="#2f9e44"></button>
                    <button type="button" class="color-swatch" style="background:#e8730f" data-color="#e8730f"></button>
                    <button type="button" class="color-swatch" style="background:#e5484d" data-color="#e5484d"></button>
                </div>
                <input type="hidden" id="classColorHex" value="#7c5cff">

                <button type="submit" class="clean-post-btn">CREATE CLASS</button>
            </form>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/classes.js"></script>
    <?php if (isset($_GET['add'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('openCreateClassModal');
            if (btn) btn.click();
        });
    </script>
    <?php endif; ?>
</body>
</html>