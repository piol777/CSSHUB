<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('student');

$roomId = $_GET['room'] ?? '';
if ($roomId === '') {
    redirect('/cdsgahub/student/live.php');
}

$stmt = $pdo->prepare("
    SELECT ls.*, c.code AS course_code, c.name AS course_name,
           u.first_name, u.last_name
    FROM live_sessions ls
    LEFT JOIN courses c ON ls.course_id = c.id
    JOIN users u ON ls.professor_id = u.id
    WHERE ls.room_id = ?
");
$stmt->execute([$roomId]);
$room = $stmt->fetch();

$stmt2 = $pdo->prepare("SELECT course_id, year_level, section_label FROM students WHERE user_id = ?");
$stmt2->execute([$_SESSION['user_id']]);
$me = $stmt2->fetch();

$eligible = $room && $me
    && (!$room['course_id'] || (int)$room['course_id'] === (int)$me['course_id'])
    && (!$room['year_level'] || (int)$room['year_level'] === (int)$me['year_level'])
    && (!$room['section_label'] || $room['section_label'] === $me['section_label']);

if (!$room || $room['status'] !== 'live' || !$eligible) {
    set_flash('error', 'That live session is no longer available.');
    redirect('/cdsgahub/student/live.php');
}

$roomLabel = ($room['course_code'] ?? 'General')
    . ($room['year_level'] ? ' ' . $room['year_level'] . 'Y' : '')
    . ($room['section_label'] ? '-' . $room['section_label'] : '');
$profName = 'Prof. ' . $room['first_name'] . ' ' . $room['last_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live - CDSGA HUB</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=2">
    <link rel="stylesheet" href="../assets/css/live_room.css">
</head>
<body class="dashboard-page live-room-page">

    <div class="room-page-layout">
    <div class="room-shell">
        <div class="room-topbar">
            <div class="room-topbar-left">
                <span class="room-live-badge">LIVE</span>
                <span class="room-title"><?= sanitize($profName . ' — ' . $roomLabel) ?></span>
            </div>
            <div class="room-topbar-right">
                <div class="room-viewer-count">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    <span id="viewerCountNum">0</span>
                </div>
            </div>
        </div>

        <div class="room-stage">
            <div class="room-main-video-wrap">
                <video id="mainVideo" autoplay playsinline></video>
                <div class="room-main-video-empty" id="mainVideoEmpty">
                    <div class="spinner"></div>
                    Connecting to the live class...
                </div>
                <div class="room-camera-off-overlay" id="cameraOffOverlay">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 16v1a2 2 0 01-2 2H3a2 2 0 01-2-2V7a2 2 0 012-2h11a2 2 0 012 2v1"></path><line x1="1" y1="1" x2="23" y2="23"></line><path d="M23 7l-7 5 7 5V7z"></path></svg>
                    <span>Camera off</span>
                </div>
            </div>
        </div>

        <div class="room-timer" id="roomTimer">00:00</div>

        <div class="room-controls-bar">
            <button type="button" class="room-ctrl-btn room-ctrl-danger" id="ctrlLeave" title="Leave">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.68 13.31a16 16 0 003.41 2.6l1.27-1.27a1 1 0 011.11-.21 12.4 12.4 0 003.9.62 1 1 0 011 1V21a1 1 0 01-1 1A18 18 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1 12.4 12.4 0 00.62 3.9 1 1 0 01-.21 1.11l-1.27 1.27"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                Leave
            </button>
        </div>
    </div>

    <aside class="room-chat-sidebar">
        <div class="room-chat-title">Live chat</div>
        <div class="room-chat-messages" id="roomChatMessages">
            <div class="room-chat-empty" id="roomChatEmpty">No messages yet.</div>
        </div>
        <div class="room-chat-input-row">
            <input type="text" class="room-chat-input" id="roomChatInput" placeholder="Type a message..." maxlength="300">
        </div>
    </aside>
    </div>

    <script>
        const IS_PROFESSOR = false;
        const ROOM_ID = <?= json_encode($room['room_id']) ?>;
        const ROOM_STARTED_AT = <?= json_encode($room['started_at']) ?>;
        const LIVE_SERVER_URL = 'http://localhost:3001';
        const CURRENT_USER_NAME = <?= json_encode($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?>;
    </script>
    <script src="http://localhost:3001/socket.io/socket.io.js"></script>
    <script src="../assets/js/live_room.js"></script>
    <script src="../assets/js/profile_card.js"></script>
</body>
</html>