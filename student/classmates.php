<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('student');

$currentPage = 'classmates';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classmates - CDSGA HUB</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=2">
    <link rel="stylesheet" href="../assets/css/classes.css?v=1">
</head>
<body class="dashboard-page">

    <?php include __DIR__ . '/../includes/student_nav.php'; ?>

    <div class="classes-page-container">
        <div class="classes-page-header">
            <div>
                <h1>Classmates</h1>
                <p>Students in your section this semester</p>
            </div>
        </div>

        <div class="classmates-list-card">
            <div class="directory-list" id="classmatesList">
                <div class="directory-empty">Loading...</div>
            </div>
        </div>
    </div>

    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/classmates.js"></script>
</body>
</html>