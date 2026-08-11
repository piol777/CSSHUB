<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('student');

$currentPage = 'live';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Class - CDSGA HUB</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=2">
    <link rel="stylesheet" href="../assets/css/live.css">
</head>
<body class="dashboard-page">

    <?php include __DIR__ . '/../includes/student_nav.php'; ?>

    <?php $flash = get_flash(); ?>
    <?php if ($flash): ?>
        <div class="flash-banner flash-<?= sanitize($flash['type']) ?>"><?= sanitize($flash['message']) ?></div>
    <?php endif; ?>

    <div class="live-container">
        <div class="live-grid" id="liveGrid">
            <div class="live-empty-state">Loading...</div>
        </div>
    </div>

    <script>
        const IS_PROFESSOR = false;
        const LIVE_SERVER_URL = 'http://localhost:3001';
    </script>
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/message_widget.js"></script>
    <script src="../assets/js/live_lobby.js"></script>
    <script src="../assets/js/profile_card.js"></script>
</body>
</html>