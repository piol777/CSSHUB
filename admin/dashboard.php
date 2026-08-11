<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('admin');

$currentPage = 'dashboard';

$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalProfessors = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'professor'")->fetchColumn();
$totalAnnouncements = $pdo->query("SELECT COUNT(*) FROM announcements")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - CDSGA HUB</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-page">

    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <div class="admin-content">
        <h1>Welcome, <?= sanitize($_SESSION['first_name']) ?></h1>

        <div style="display:flex; gap:20px;">
            <div class="admin-card" style="flex:1;">
                <div style="color:#9a97b8; font-size:13px;">Total Students</div>
                <div style="color:#fff; font-size:28px; font-weight:700; margin-top:8px;"><?= $totalStudents ?></div>
            </div>
            <div class="admin-card" style="flex:1;">
                <div style="color:#9a97b8; font-size:13px;">Total Professors</div>
                <div style="color:#fff; font-size:28px; font-weight:700; margin-top:8px;"><?= $totalProfessors ?></div>
            </div>
            <div class="admin-card" style="flex:1;">
                <div style="color:#9a97b8; font-size:13px;">Total Announcements</div>
                <div style="color:#fff; font-size:28px; font-weight:700; margin-top:8px;"><?= $totalAnnouncements ?></div>
            </div>
        </div>
    </div>

</body>
</html> 