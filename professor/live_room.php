<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('professor');

$roomId = $_GET['room'] ?? '';
if ($roomId === '') {
    redirect('/cdsgahub/professor/live.php');
}

$stmt = $pdo->prepare("
    SELECT ls.*, c.code AS course_code, c.name AS course_name
    FROM live_sessions ls
    LEFT JOIN courses c ON ls.course_id = c.id
    WHERE ls.room_id = ?
");
$stmt->execute([$roomId]);
$room = $stmt->fetch();

if (!$room || (int)$room['professor_id'] !== (int)$_SESSION['user_id'] || $room['status'] !== 'live') {
    set_flash('error', 'That live session is no longer active.');
    redirect('/cdsgahub/professor/live.php');
}

$roomLabel = ($room['course_code'] ?? 'General')
    . ($room['year_level'] ? ' ' . $room['year_level'] . 'Y' : '')
    . ($room['section_label'] ? '-' . $room['section_label'] : '');

$picStmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$picStmt->execute([$_SESSION['user_id']]);
$myAvatar = $picStmt->fetchColumn();
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
        <div class="room-participants-bar" id="participantsBar"></div>

        <div class="room-topbar">
            <div class="room-topbar-left">
                <span class="room-live-badge">LIVE</span>
                <span class="room-title"><?= sanitize($roomLabel) ?></span>
            </div>
            <div class="room-topbar-right">
                <button type="button" class="room-theme-toggle" id="roomThemeToggle" title="Toggle theme">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                </button>
                <div class="room-viewer-count">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    <span id="viewerCountNum">0</span>
                </div>
                <div class="stream-request-wrap">
                    <button type="button" class="room-theme-toggle" id="streamRequestToggle" title="Stream requests">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 7l-7 5 7 5V7z"></path><rect x="1" y="5" width="15" height="14" rx="2"></rect></svg>
                        <span class="stream-request-badge hidden" id="streamRequestBadge">0</span>
                    </button>
                    <div class="stream-request-panel" id="streamRequestPanel">
                        <div class="stream-request-panel-header">Stream Requests</div>
                        <div class="stream-request-list" id="streamRequestList">
                            <div class="stream-request-empty">No requests yet.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="room-stage">
            <div class="room-main-video-wrap">
                <video id="mainVideo" autoplay muted playsinline></video>
                <div class="room-main-video-empty" id="mainVideoEmpty">
                    <div class="spinner"></div>
                    Starting camera...
                </div>
                <div class="room-camera-off-overlay" id="cameraOffOverlay">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 16v1a2 2 0 01-2 2H3a2 2 0 01-2-2V7a2 2 0 012-2h11a2 2 0 012 2v1"></path><line x1="1" y1="1" x2="23" y2="23"></line><path d="M23 7l-7 5 7 5V7z"></path></svg>
                    <span>Camera off</span>
                </div>
                <div class="room-pip-wrap" id="roomPipWrap" style="display:none;">
                    <video id="pipVideo" autoplay muted playsinline></video>
                    <div class="room-pip-off-label" id="pipOffLabel">Camera off</div>
                </div>
            </div>
        </div>

        <div class="presenter-box" id="presenterBox" style="display:none;">
            <div class="presenter-box-label" id="presenterBoxLabel">Presenting</div>
            <video id="presenterVideo" autoplay playsinline></video>
            <button type="button" class="presenter-box-stop" id="presenterBoxStopBtn" title="Stop this student's stream">&times;</button>
        </div>

        <div class="room-timer" id="roomTimer">00:00</div>

        <div class="room-controls-bar">
            <button type="button" class="room-ctrl-btn state-on" id="ctrlCamera" title="Camera">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 7l-7 5 7 5V7z"></path><rect x="1" y="5" width="15" height="14" rx="2"></rect></svg>
            </button>
            <button type="button" class="room-ctrl-btn state-on" id="ctrlMic" title="Microphone">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"></path><path d="M19 10v2a7 7 0 01-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="23"></line></svg>
            </button>
            <button type="button" class="room-ctrl-btn" id="ctrlScreen" title="Share screen">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
            </button>
            <button type="button" class="room-ctrl-btn room-ctrl-danger" id="ctrlEnd" title="End">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.68 13.31a16 16 0 003.41 2.6l1.27-1.27a1 1 0 011.11-.21 12.4 12.4 0 003.9.62 1 1 0 011 1V21a1 1 0 01-1 1A18 18 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1 12.4 12.4 0 00.62 3.9 1 1 0 01-.21 1.11l-1.27 1.27"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                End
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

    <!-- Leave vs Delete popup -->
    <div class="room-popup-overlay" id="endRoomPopup">
        <div class="room-popup-box">
            <h3>End your live class?</h3>
            <p>Leave lang para makapag-minimize ka pero manatiling live ang session, o Delete para tapusin ito para sa lahat.</p>
            <button type="button" class="room-popup-btn" id="popupLeaveBtn">Leave (room stays live)</button>
            <button type="button" class="room-popup-btn room-popup-btn-danger" id="popupDeleteBtn">Delete (end for everyone)</button>
            <button type="button" class="room-popup-btn-cancel" id="popupCancelBtn">Cancel</button>
        </div>
    </div>

    <script>
        const IS_PROFESSOR = true;
        const ROOM_ID = <?= json_encode($room['room_id']) ?>;
        const ROOM_STARTED_AT = <?= json_encode($room['started_at']) ?>;
        const LIVE_SERVER_URL = 'http://localhost:3001';
        const CURRENT_USER_NAME = <?= json_encode('Prof. ' . $_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?>;
        const CURRENT_USER_AVATAR = <?= json_encode($myAvatar ?: null) ?>;
    </script>
    <script src="http://localhost:3001/socket.io/socket.io.js"></script>
    <script src="../assets/js/live_room.js"></script>
    <script src="../assets/js/profile_card.js"></script>
</body>
</html>