<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('professor');

$currentPage = 'classes';
$class_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT cs.*, c.code AS course_code
    FROM class_sections cs
    JOIN courses c ON c.id = cs.course_id
    WHERE cs.id = ? AND cs.professor_id = ?
");
$stmt->execute([$class_id, $_SESSION['user_id']]);
$class = $stmt->fetch();

if (!$class) {
    redirect('/cdsgahub/professor/classes.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($class['subject_name']) ?> - CDSGA HUB</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=2">
    <link rel="stylesheet" href="../assets/css/classes.css?v=1">
</head>
<body class="dashboard-page">

    <?php include __DIR__ . '/../includes/professor_nav.php'; ?>

    <div class="classes-page-container" data-class-id="<?= (int)$class['id'] ?>">
        <div class="class-details-header">
            <div class="class-details-icon">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
            </div>
            <div>
                <div class="class-details-title"><?= sanitize($class['subject_name']) ?></div>
                <div class="class-details-subtitle"><?= sanitize($class['course_code']) ?> <?= (int)$class['year_level'] ?>-<?= sanitize($class['section_label']) ?> &middot; <?= sanitize($class['semester_label']) ?></div>
            </div>
        </div>

        <div class="attendance-panel">
            <div class="attendance-panel-header">
                <h3>Take Attendance</h3>
                <input type="date" class="attendance-date-input" id="attendanceDate" value="<?= date('Y-m-d') ?>">
            </div>
            <div id="attendanceList">
                <div class="classes-empty">Loading...</div>
            </div>
            <button type="button" class="attendance-save-btn" id="saveAttendanceBtn" style="display:none;">Save Attendance</button>
        </div>

        <div class="attendance-panel">
            <div class="attendance-panel-header">
                <h3>Attendance History</h3>
            </div>
            <div class="attendance-history" id="attendanceHistory">
                <div class="classes-empty">Loading...</div>
            </div>
        </div>

        <div class="attendance-panel">
            <div class="attendance-panel-header">
                <h3>Assignments</h3>
                <button type="button" class="classes-add-btn" id="openCreateAssignmentModal">+ New Assignment</button>
            </div>
            <div id="assignmentsList">
                <div class="classes-empty">Loading...</div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="createAssignmentModal">
        <div class="modal-box">
            <div class="modal-header">
                <span>New Assignment</span>
                <button type="button" class="modal-close-btn" id="closeCreateAssignmentModal">&times;</button>
            </div>
            <form id="createAssignmentForm" class="modal-form">
                <label>Title</label>
                <input type="text" id="assignmentTitle" placeholder="e.g. Midterm Project" required maxlength="200">
                <label>Description</label>
                <textarea id="assignmentDescription" rows="3" placeholder="Instructions (optional)"></textarea>
                <label>Points</label>
                <input type="number" id="assignmentPoints" value="100" min="1" required>
                <label>Due Date</label>
                <input type="datetime-local" id="assignmentDueDate">
                <button type="submit" class="modal-submit-btn">Create Assignment</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="viewSubmissionsModal">
        <div class="modal-box modal-box-wide">
            <div class="modal-header">
                <span id="submissionsModalTitle">Submissions</span>
                <button type="button" class="modal-close-btn" id="closeViewSubmissionsModal">&times;</button>
            </div>
            <div id="submissionsList" class="submissions-list"></div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/class_details.js"></script>
</body>
</html>