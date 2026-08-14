<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('professor');

$currentPage = 'live';

$mySections = $pdo->prepare("
    SELECT cs.id, cs.subject_name, cs.year_level, cs.section_label, cs.course_id, c.code AS course_code
    FROM class_sections cs
    JOIN courses c ON c.id = cs.course_id
    WHERE cs.professor_id = ?
    ORDER BY cs.created_at DESC
");
$mySections->execute([$_SESSION['user_id']]);
$mySections = $mySections->fetchAll();
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

    <?php include __DIR__ . '/../includes/professor_nav.php'; ?>

    <?php $flash = get_flash(); ?>
    <?php if ($flash): ?>
        <div class="flash-banner flash-<?= sanitize($flash['type']) ?>"><?= sanitize($flash['message']) ?></div>
    <?php endif; ?>

    <div class="live-container">
        <div class="live-grid" id="liveGrid">
            <div class="live-empty-state">Loading...</div>
        </div>
    </div>

    <!-- Create Room Modal -->
    <div class="modal-overlay" id="createRoomModal">
        <div class="create-room-box" id="createRoomStep">
            <h2>Create Room</h2>
            <?php if (empty($mySections)): ?>
                <p style="color:#ff8a94; font-size:13px; text-align:center; margin:12px 0;">You have no sections assigned yet. Ask your admin to assign one.</p>
            <?php else: ?>
                <select class="create-room-field" id="roomSectionSelect">
                    <option value="">Select your section</option>
                    <?php foreach ($mySections as $s): ?>
                        <option value="<?= $s['id'] ?>" data-course-id="<?= $s['course_id'] ?>" data-year-level="<?= $s['year_level'] ?>" data-section-label="<?= sanitize($s['section_label']) ?>">
                            <?= sanitize($s['course_code']) ?> <?= $s['year_level'] ?>-<?= sanitize($s['section_label']) ?> — <?= sanitize($s['subject_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <button class="create-room-go-btn" id="goLiveBtn" <?= empty($mySections) ? 'disabled' : '' ?>>Next</button>
        </div>

        <div class="pick-live-box" id="modeStep" style="display:none;">
            <h2>Live Mode</h2>
            <div class="live-mode-options">
                <button type="button" class="live-mode-btn" data-mode="gaming">🎮 Gaming</button>
                <button type="button" class="live-mode-btn" data-mode="live_class">🏫 Live Class</button>
                <button type="button" class="live-mode-btn" data-mode="other">✏️ Other</button>
            </div>
            <input type="text" class="create-room-field" id="modeOtherInput" placeholder="Type your mode..." style="display:none; margin-top:12px;" maxlength="50">
            <button class="create-room-go-btn" id="confirmModeBtn" style="margin-top:14px;" disabled>Go Live</button>
        </div>

        <div class="pick-live-box" id="pickLiveStep" style="display:none;">
            <h2>Pick live</h2>
            <div class="pick-live-grid">
                <?php foreach (get_live_categories() as $code => $cat): ?>
                    <button type="button" class="pick-live-item" data-live-type="<?= sanitize($code) ?>">
                        <img src="../assets/images/live-categories/<?= sanitize($cat['image']) ?>" alt="<?= sanitize($cat['label']) ?>">
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="settings-format-wrap" id="settingsFormatStep" style="display:none;">
            <div class="settings-box">
                <h2>Settings</h2>
                <div class="settings-toggles">
                    <button type="button" class="settings-toggle-btn" id="toggleCamera" data-media="camera">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 7l-7 5 7 5V7z"></path><rect x="1" y="5" width="15" height="14" rx="2"></rect></svg>
                        <span class="settings-toggle-label" id="cameraStateLabel">On</span>
                    </button>
                    <button type="button" class="settings-toggle-btn" id="toggleScreen" data-media="screen">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                        <span class="settings-toggle-label" id="screenStateLabel">On</span>
                    </button>
                    <button type="button" class="settings-toggle-btn" id="toggleMic" data-media="mic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"></path><path d="M19 10v2a7 7 0 01-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="23"></line></svg>
                        <span class="settings-toggle-label" id="micStateLabel">On</span>
                    </button>
                </div>
                <div class="settings-countdown" id="settingsCountdown">40s</div>
                <button class="create-room-go-btn" id="finalGoBtn">Go</button>
            </div>

            <div class="format-box">
                <h2>Format</h2>
                <div class="format-body">
                    <div class="format-preview" id="formatPreview">
                        <video id="formatPreviewVideo" autoplay muted playsinline></video>
                        <div class="format-pip" id="formatPip" style="display:none;">
                            <video id="formatPipVideo" autoplay muted playsinline></video>
                        </div>
                    </div>
                    <div class="format-icons">
                        <span class="format-side-icon" id="formatCameraIcon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 7l-7 5 7 5V7z"></path><rect x="1" y="5" width="15" height="14" rx="2"></rect></svg>
                        </span>
                        <span class="format-side-icon" id="formatScreenIcon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                        </span>
                        <span class="format-side-icon" id="formatMicIcon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 1a3 3 0 00-3 3v8a3 3 0 006 0V4a3 3 0 00-3-3z"></path><path d="M19 10v2a7 7 0 01-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="23"></line></svg>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const IS_PROFESSOR = true;
        const LIVE_SERVER_URL = 'http://localhost:3001';
    </script>
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/create_post.js"></script>
    <script src="../assets/js/message_widget.js"></script>
    <?php if (isset($_GET['create'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('createRoomModal');
            if (modal) {
                modal.classList.add('open');
            }
        });
    </script>
    <?php endif; ?>
    <script src="../assets/js/live_lobby.js"></script>
    <script src="../assets/js/profile_card.js"></script>
</body>
</html>