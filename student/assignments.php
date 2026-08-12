<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('student');

$currentPage = 'assignments';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments - CDSGA HUB</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=2">
    <link rel="stylesheet" href="../assets/css/classes.css?v=1">
</head>
<body class="dashboard-page">

    <?php include __DIR__ . '/../includes/student_nav.php'; ?>

    <div class="classes-page-container">
        <div class="classes-page-header">
            <div>
                <h1>My Assignments</h1>
                <p>Assignments from your professors this semester</p>
            </div>
        </div>

        <div id="studentAssignmentsList">
            <div class="classes-empty">Loading...</div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/warning_policy.js"></script>
    <script src="../assets/js/student_assignments.js"></script>
</body>
</html>