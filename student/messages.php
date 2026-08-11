<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('student');

$currentPage = 'messages';
$openProfessorId = isset($_GET['professor_id']) ? (int)$_GET['professor_id'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - CDSGA HUB</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=2">
    <link rel="stylesheet" href="../assets/css/messages.css?v=2">
</head>
<body class="dashboard-page messages-fullscreen">

    <?php include __DIR__ . '/../includes/student_nav.php'; ?>

    <div class="messages-layout">
        <div class="conv-list-panel" id="convListPanel">
            <div class="conv-list-header">Messages</div>
            <div class="conv-list" id="convList">
                <div class="conv-empty">Loading...</div>
            </div>
        </div>

        <div class="chat-window" id="chatWindow">
            <div class="chat-window-empty" id="chatEmptyState">Select a conversation to start chatting.</div>
        </div>
    </div>

    <script>
        const CURRENT_ROLE = 'student';
        const AUTO_OPEN_USER_ID = <?= $openProfessorId ? (int)$openProfessorId : 'null' ?>;
    </script>
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/messages.js"></script>
    <script src="../assets/js/profile_card.js"></script>
</body>
</html>