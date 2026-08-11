<?php
if (!isset($currentPage)) { $currentPage = ''; }
$fullName = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$navPicStmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
$navPicStmt->execute([$_SESSION['user_id']]);
$navProfilePic = $navPicStmt->fetchColumn();
$dailyVerse = get_daily_verse();
?>
<nav class="top-nav">
    <div class="nav-left">
        <button class="icon-btn" id="menuToggle" title="Menu">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round">
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
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

        <button class="icon-btn" id="themeToggle" title="Toggle theme">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path>
            </svg>
        </button>

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

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<div class="app-sidebar" id="appSidebar">
    <div class="sidebar-verse-card">
    <div class="sidebar-verse-title">DAILY VERSE</div>
    <div class="sidebar-verse-image" style="background-image: url('../assets/images/daily-verse-bg.jpg')"></div>
    <div class="sidebar-verse-text">&ldquo;<?= sanitize($dailyVerse['text']) ?>&rdquo;</div>
    <div class="sidebar-verse-ref">&mdash; <?= sanitize($dailyVerse['reference']) ?></div>
</div>

<div class="sidebar-settings-group">
        <a href="#" class="sidebar-settings-btn" id="notifSettingsBtn">Notification Settings</a>
        <a href="#" class="sidebar-settings-btn" id="settingsBtn">Settings</a>
        <a href="assignments.php" class="sidebar-settings-btn">My Assignments</a>
        <a href="classmates.php" class="sidebar-settings-btn">Classmates</a>
        <a href="profile.php" class="sidebar-settings-btn">Edit profile</a>
        <a href="../auth/logout.php" class="sidebar-settings-btn" id="logoutBtn">Log out</a>
    </div>

    <div class="sidebar-footer" data-profile-user-id="<?= (int) $_SESSION['user_id'] ?>">
        <div class="avatar-circle"<?php if ($navProfilePic): ?> style="background-image: url('../<?= sanitize($navProfilePic) ?>')"<?php endif; ?>></div>
    </div>
</div>