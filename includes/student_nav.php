<?php
if (!isset($currentPage)) { $currentPage = ''; }
$fullName = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$navPicStmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$navPicStmt->execute([$_SESSION['user_id']]);
$navProfilePic = $navPicStmt->fetchColumn();
$dailyVerse = get_daily_verse();

$isMayorStmt = $pdo->prepare("SELECT is_mayor FROM students WHERE user_id = ?");
$isMayorStmt->execute([$_SESSION['user_id']]);
$isMayor = (bool)$isMayorStmt->fetchColumn();

$navCourses = $pdo->query("SELECT id, code, name FROM courses ORDER BY name ASC")->fetchAll();
?>
<nav class="top-nav">
    <div class="nav-left">
        <span class="logo">CDSGA HUB</span>
    </div>

    <div class="nav-center">
        <a href="dashboard.php" class="icon-btn <?= $currentPage === 'home' ? 'active' : '' ?>" title="Home">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9.5L12 3l9 6.5V21a1 1 0 01-1 1h-5v-7H9v7H4a1 1 0 01-1-1V9.5z"></path>
            </svg>
        </a>

        <div class="nav-item-wrapper">
            <button class="icon-btn" id="notifToggle" title="Notifications">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 01-3.46 0"></path>
                </svg>
                <span class="notif-badge hidden" id="notifBadge">0</span>
            </button>

            <div class="notif-panel" id="notifPanel">
                <div class="notif-panel-header">Notification</div>
                <div class="notif-list" id="notifList">
                    <div class="notif-empty">Loading...</div>
                </div>
            </div>
        </div>

        <?php if (!$isMayor): ?>
        <button class="icon-btn" id="themeToggle" title="Toggle theme">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path>
            </svg>
        </button>
        <?php else: ?>
        <div class="nav-item-wrapper">
            <button class="icon-btn" id="mayorCreateToggle" title="Create">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </button>
        </div>
        <?php endif; ?>

        <a href="live.php" class="icon-btn <?= $currentPage === 'live' ? 'active' : '' ?>" title="Live Class">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="23 7 16 12 23 17 23 7"></polygon>
                <rect x="1" y="5" width="15" height="14" rx="2"></rect>
            </svg>
        </a>
        <div class="nav-item-wrapper">
            <button class="icon-btn" id="msgWidgetToggle" title="Messages">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"></path>
                </svg>
                <span class="notif-badge hidden" id="msgBadge">0</span>
            </button>
        </div>
    </div>

    <div class="nav-right">
        <?php if ($isMayor): ?>
        <button class="icon-btn" id="themeToggle" title="Toggle theme">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path>
            </svg>
        </button>
        <?php endif; ?>
        <div class="nav-item-wrapper">
            <button class="icon-btn" id="warningToggle" title="Warning Policy">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span class="notif-badge hidden" id="warningBadge">0</span>
            </button>

            <div class="warning-policy-panel" id="warningPolicyPanel">
                <div class="warning-policy-title">Warning Policy</div>
                <div class="warning-policy-text">
                    ⚠ Student Warning Status<br><br>
                    This student currently has <strong id="warningPolicyCount">0</strong> out of 3 warnings.
                    Receiving 3 warnings will temporarily restrict the student's access to the system. The student will
                    no longer be able to view professor posts or announcements or join live sessions.<br><br>
                    The student must coordinate with OSAS (Office of Student Affairs and Services) regarding the warnings.
                    After the matter has been addressed, an authorized professor or administrator may reset
                    the warning count to 0 and restore the student's access.<br><br>
                    Warnings: <strong id="warningPolicyCount2">0</strong> / 3
                </div>
            </div>
        </div>

        <div class="nav-item-wrapper profile-nav-wrapper">
            <button class="profile-nav-btn" id="profileNavToggle" title="Profile">
                <div class="avatar-circle profile-nav-avatar"<?php if ($navProfilePic): ?> style="background-image: url('../<?= sanitize($navProfilePic) ?>')"<?php endif; ?>></div>
            </button>

            <div class="profile-nav-dropdown" id="profileNavDropdown">
                <div class="profile-nav-dropdown-header">
                    <div class="avatar-circle profile-nav-dropdown-avatar"<?php if ($navProfilePic): ?> style="background-image: url('../<?= sanitize($navProfilePic) ?>')"<?php endif; ?>></div>
                    <div class="profile-nav-dropdown-info">
                        <div class="profile-nav-dropdown-name"><?= sanitize($fullName) ?></div>
                        <div class="profile-nav-dropdown-role">Student</div>
                    </div>
                </div>
                <a href="profile.php" class="profile-nav-dropdown-option">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    View Profile
                </a>
                <a href="#" class="profile-nav-dropdown-option">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 01-3.46 0"></path></svg>
                    Notification Settings
                </a>
                <a href="#" class="profile-nav-dropdown-option">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"></path></svg>
                    Settings
                </a>
                <a href="../auth/logout.php" class="profile-nav-dropdown-option profile-nav-logout logout-confirm-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Log out
                </a>
            </div>
        </div>

        <?php if ($isMayor): ?>
<div class="modal-overlay" id="createPostModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Create Post</h2>
            <button class="modal-close-btn" id="closeCreatePostModal">&times;</button>
        </div>
        <form id="createPostForm" enctype="multipart/form-data">
            <div class="modal-form-group">
                <label for="postTitle">Title</label>
                <input type="text" id="postTitle" name="title" maxlength="200" required>
            </div>
            <div class="modal-form-group">
                <label for="postContent">Content</label>
                <textarea id="postContent" name="content" maxlength="2000" required></textarea>
            </div>
            <button type="submit" class="modal-submit-btn">Post Announcement</button>
        </form>
    </div>
</div>
<?php endif; ?>

        <!-- Floating Message Widget -->
        <div class="msg-widget" id="msgWidget">
            <div class="msg-widget-header">
                <div class="msg-widget-title">
                    <button class="msg-widget-back" id="msgWidgetBack" style="display:none;">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </button>
                    <div class="avatar-circle msg-widget-header-avatar" id="msgWidgetHeaderAvatar" style="display:none;"></div>
                    <span id="msgWidgetTitleText">Messages</span>
                </div>
                <div class="msg-widget-actions">
                    <a href="messages.php" class="msg-widget-icon-btn" title="Open full view">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"></path><path d="M9 21H3v-6"></path><path d="M21 3l-7 7"></path><path d="M3 21l7-7"></path></svg>
                    </a>
                    <button class="msg-widget-icon-btn" id="msgWidgetClose" title="Close">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            </div>

            <div class="msg-widget-list" id="msgWidgetList">
                <div class="msg-widget-empty">Loading...</div>
            </div>

            <div class="msg-widget-chat" id="msgWidgetChat" style="display:none;">
                <div class="msg-widget-messages" id="msgWidgetMessages"></div>
                <div class="msg-widget-input-area">
                    <div class="msg-widget-file-preview-row" id="msgWidgetFilePreview" style="display:none;"></div>
                    <form class="msg-widget-input-row" id="msgWidgetForm">
                        <input type="text" id="msgWidgetInput" placeholder="Type a message..." maxlength="2000" autocomplete="off">
                        <input type="file" id="msgWidgetFileInput" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip" hidden>
                        <button type="button" class="msg-widget-attach-btn" id="msgWidgetAttachBtn" title="Attach image or file">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="M21 15l-5-5L5 21"></path></svg>
                        </button>
                        <button type="submit" class="msg-widget-send-btn">
                            <svg viewBox="0 0 24 24"><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                        </button>
                    </form>
                </div>
            </div>

            <div class="msg-widget-reaction-picker" id="msgWidgetReactionPicker">
                <button type="button" data-reaction="like">👍</button>
                <button type="button" data-reaction="love">❤️</button>
                <button type="button" data-reaction="haha">😂</button>
                <button type="button" data-reaction="wow">😮</button>
                <button type="button" data-reaction="sad">😢</button>
                <button type="button" data-reaction="angry">😠</button>
            </div>
        </div>
    </div>
</nav>
