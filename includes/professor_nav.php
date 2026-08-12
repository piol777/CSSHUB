<?php
if (!isset($currentPage)) { $currentPage = ''; }
$fullName = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$navCourses = $pdo->query("SELECT id, code, name FROM courses ORDER BY name ASC")->fetchAll();
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
        <div class="my-upcoming-wrap">
            <button type="button" class="my-upcoming-toggle" id="myUpcomingToggle" title="My Upcoming Posts">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 114 4L7.5 20.5 2 22l1.5-5.5z"></path></svg>
            </button>
            <div class="my-upcoming-panel" id="myUpcomingPanel">
                <div class="my-upcoming-panel-header">My Upcoming Posts</div>
                <div class="upcoming-list" id="myUpcomingList">
                    <div class="upcoming-empty">Loading...</div>
                </div>
            </div>
        </div>
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

        <div class="nav-item-wrapper">
            <button class="icon-btn" id="createToggle" title="Create">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </button>

            <div class="create-panel" id="createPanel">
                <div class="create-panel-header">CREATE</div>
                <a href="#" class="create-option" id="openCreatePostModal">
                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Post
                </a>
                <a href="#" class="create-option" id="quickLiveOption">
                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M23 7l-7 5 7 5V7z"></path>
                        <rect x="1" y="5" width="15" height="14" rx="2"></rect>
                    </svg>
                    Live
                </a>
            </div>
        </div>

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
        <button class="icon-btn" id="themeToggle" title="Toggle theme">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path>
            </svg>
        </button>
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
        <a href="classes.php" class="sidebar-settings-btn">Manage Classes</a>
        <a href="profile.php" class="sidebar-settings-btn">Edit profile</a>
        <a href="../auth/logout.php" class="sidebar-settings-btn" id="logoutBtn">Log out</a>
    </div>

    <div class="sidebar-footer" data-profile-user-id="<?= (int) $_SESSION['user_id'] ?>">
        <div class="avatar-circle"<?php if ($navProfilePic): ?> style="background-image: url('../<?= sanitize($navProfilePic) ?>')"<?php endif; ?>></div>
    </div>
</div>

<!-- Create Post Modal -->
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
            <div class="modal-form-group">
                <label for="postImages">Images (optional, max 2)</label>
                <input type="file" id="postImages" name="images[]" accept="image/*" multiple>
                <div id="imagePreviewRow" style="display:flex; gap:8px; margin-top:8px;"></div>
            </div>
            <div class="modal-form-group">
                <label for="postAttachment">Attachment (optional, 1 file, max 20MB)</label>
                <input type="file" id="postAttachment" name="attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.csv,.zip,.rar,.rtf,.odt">
                <div id="attachmentPreviewRow" style="margin-top:8px;"></div>
            </div>
            <div class="modal-form-row">
                <div class="modal-form-group" style="flex:1;">
                    <label for="postCourse">Course (optional)</label>
                    <select id="postCourse" name="target_course_id">
                        <option value="">All courses</option>
                    </select>
                </div>
                <div class="modal-form-group" style="flex:1;">
                    <label for="postYearLevel">Year Level (optional)</label>
                    <select id="postYearLevel" name="target_year_level">
                        <option value="">All year levels</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
            </div>
            <div class="modal-form-group">
                <label for="postSection">Section (optional)</label>
                <input type="text" id="postSection" name="target_section_label" placeholder="e.g. 1-1 (leave blank for all sections)">
            </div>
            <div class="modal-hint">Leave targeting fields blank to post to everyone.</div>
            <button type="submit" class="modal-submit-btn">Post Announcement</button>
        </form>
    </div>
</div>

<!-- Edit Post Modal -->
<div class="modal-overlay" id="editPostModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2>Edit Post</h2>
            <button class="modal-close-btn" id="closeEditPostModal">&times;</button>
        </div>
        <form id="editPostForm">
            <input type="hidden" id="editPostId" name="id">
            <div class="modal-form-group">
                <label for="editPostTitle">Title</label>
                <input type="text" id="editPostTitle" name="title" maxlength="200" required>
            </div>
            <div class="modal-form-group">
                <label for="editPostContent">Content</label>
                <textarea id="editPostContent" name="content" maxlength="2000" required></textarea>
            </div>
            <div class="modal-form-row">
                <div class="modal-form-group" style="flex:1;">
                    <label for="editPostCourse">Course (optional)</label>
                    <select id="editPostCourse" name="target_course_id">
                        <option value="">All courses</option>
                    </select>
                </div>
                <div class="modal-form-group" style="flex:1;">
                    <label for="editPostYearLevel">Year Level (optional)</label>
                    <select id="editPostYearLevel" name="target_year_level">
                        <option value="">All year levels</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
            </div>
            <div class="modal-form-group">
                <label for="editPostSection">Section (optional)</label>
                <input type="text" id="editPostSection" name="target_section_label" placeholder="e.g. 1-1 (leave blank for all sections)">
            </div>
            <div class="modal-hint">Images and attachments can't be changed here — delete and repost if you need to change those.</div>
            <button type="submit" class="modal-submit-btn">Save Changes</button>
        </form>
    </div>
</div>

<!-- Post Upcoming Modal -->
<div class="modal-overlay" id="createUpcomingModal">
    <div class="modal-box">
        <div class="modal-header">
            <h2 id="upcomingModalTitle">Post Upcoming</h2>
            <button class="modal-close-btn" id="closeCreateUpcomingModal">&times;</button>
        </div>
        <form id="createUpcomingForm">
            <div class="modal-form-group">
                <input type="hidden" id="upcomingEditId" value="">
                <label for="upcomingTitle">Title</label>
                <input type="text" id="upcomingTitle" name="title" maxlength="200" required placeholder="e.g. Web Development">
            </div>
            <div class="modal-form-row">
                <div class="modal-form-group" style="flex:1;">
                    <label for="upcomingType">Type</label>
                    <select id="upcomingType" name="event_type">
                        <option value="class">Class</option>
                        <option value="live">Live Class</option>
                        <option value="exam">Exam</option>
                        <option value="event">Event</option>
                    </select>
                </div>
                <div class="modal-form-group" style="flex:1;">
                    <label for="upcomingDate">Date</label>
                    <input type="date" id="upcomingDate" name="event_date" required>
                </div>
                <div class="modal-form-group" style="flex:1;">
                    <label for="upcomingTime">Time (optional)</label>
                    <input type="time" id="upcomingTime" name="event_time">
                </div>
            </div>
            <div class="modal-form-row">
                <div class="modal-form-group" style="flex:1;">
                    <label for="upcomingCourse">Course (optional)</label>
                    <select id="upcomingCourse" name="target_course_id">
                        <option value="">All courses</option>
                    </select>
                </div>
                <div class="modal-form-group" style="flex:1;">
                    <label for="upcomingYearLevel">Year Level (optional)</label>
                    <select id="upcomingYearLevel" name="target_year_level">
                        <option value="">All year levels</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
            </div>
            <div class="modal-form-group">
                <label for="upcomingSection">Section (optional)</label>
                <input type="text" id="upcomingSection" name="target_section_label" placeholder="e.g. 1-1 (leave blank for all sections)">
            </div>
            <div class="modal-hint">Leave targeting fields blank to show to everyone.</div>
            <button type="submit" class="modal-submit-btn">Post Upcoming</button>
        </form>
    </div>
</div>

<div class="toast" id="toast"></div>

<!-- Directory Panel (Professor only) -->
<div class="directory-widget" id="directoryWidget">
    <div class="directory-header">
        <div class="msg-widget-title">
            <button class="msg-widget-back" id="directoryBackBtn" title="Close">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
            <div class="directory-online-indicator">Students</div>
        </div>
        <button class="directory-filter-btn" id="directoryFilterToggle">Select Course/Year/Section</button>
    </div>
    <div class="directory-filter-form" id="directoryFilterForm">
        <select id="directoryCourse">
            <option value="">All courses</option>
        </select>
        <select id="directoryYear">
            <option value="">All year levels</option>
            <option value="1">1st Year</option>
            <option value="2">2nd Year</option>
            <option value="3">3rd Year</option>
            <option value="4">4th Year</option>
        </select>
        <input type="text" id="directorySection" placeholder="Section (e.g. 1-1, optional)">
        <button type="button" id="directoryApplyFilter">Apply</button>
    </div>
    <div class="directory-list" id="directoryList">
        <div class="directory-empty">Select a filter to see students.</div>
    </div>
</div>

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
            <button class="msg-widget-icon-btn" id="directoryToggle" title="Find students">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 00-3-3.87"></path><path d="M16 3.13a4 4 0 010 7.75"></path></svg>
            </button>
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