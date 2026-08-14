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
        <span class="logo">CDSGA HUB</span>
        <div class="my-upcoming-wrap">
            <button type="button" class="my-upcoming-toggle" id="classOverviewToggle" title="Class Overview">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            </button>
            <div class="my-upcoming-panel class-overview-panel" id="classOverviewPanel">
                <div class="my-upcoming-panel-header">Class Overview</div>
                <div id="classOverviewList">
                    <div class="upcoming-empty">Loading...</div>
                </div>
                <a href="classes.php" class="class-overview-view-all" style="margin:0 16px 14px; display:block;">View All Classes</a>
            </div>
        </div>

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
        <div class="nav-center-spacer" aria-hidden="true"></div>
        <a href="dashboard.php" class="icon-btn <?= $currentPage === 'home' ? 'active' : '' ?>" title="Home">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 9.5L12 3l9 6.5V21a1 1 0 01-1 1h-5v-7H9v7H4a1 1 0 01-1-1V9.5z"></path>
            </svg>
        </a>

        <div class="nav-item-wrapper">
            <button class="icon-btn" id="pinnedToggle" title="Pinned Posts">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="17" x2="12" y2="22"></line><path d="M5 17h14v-1.76a2 2 0 00-1.11-1.79l-1.78-.9A2 2 0 0115 10.76V6h1a2 2 0 000-4H8a2 2 0 000 4h1v4.76a2 2 0 01-1.11 1.79l-1.78.9A2 2 0 005 15.24V17z"></path></svg>
            </button>
            <div class="notif-panel" id="pinnedPanel">
                <div class="notif-panel-header">Pinned Posts</div>
                <div class="notif-list" id="pinnedList">
                    <div class="notif-empty">Loading...</div>
                </div>
            </div>
        </div>

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
                <a href="#" class="create-option" id="openCreatePostModal" style="display:none;">
                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Post
                </a>
                <a href="classes.php" class="create-option">
                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    Add Class
                </a>
                <a href="#" class="create-option" id="openCreateUpcomingModal">
                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    Upcoming
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

        <a href="studio.php" class="icon-btn <?= $currentPage === 'live' ? 'active' : '' ?>" title="Live Class">
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

        <div class="nav-item-wrapper nav-search-wrap">
            <div class="nav-search-box">
                <button type="button" class="nav-search-icon-btn" id="navSearchBtn" title="Search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>
                <input type="text" id="navSearchInput" class="nav-search-input" placeholder="Search students..." autocomplete="off">
            </div>

            <div class="nav-search-results" id="navSearchResults"></div>
        </div>
    </div>

    <div class="nav-right">
        <button class="icon-btn" id="themeToggle" title="Toggle theme">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"></path>
            </svg>
        </button>

        <div class="nav-item-wrapper profile-nav-wrapper">
            <button class="profile-nav-btn" id="profileNavToggle" title="Profile">
                <div class="avatar-circle profile-nav-avatar"<?php if ($navProfilePic): ?> style="background-image: url('../<?= sanitize($navProfilePic) ?>')"<?php endif; ?>></div>
            </button>

            <div class="profile-nav-dropdown" id="profileNavDropdown">
                <div class="profile-nav-dropdown-header">
                    <div class="avatar-circle profile-nav-dropdown-avatar"<?php if ($navProfilePic): ?> style="background-image: url('../<?= sanitize($navProfilePic) ?>')"<?php endif; ?>></div>
                    <div class="profile-nav-dropdown-info">
                        <div class="profile-nav-dropdown-name"><?= sanitize($fullName) ?></div>
                        <div class="profile-nav-dropdown-role">Professor</div>
                    </div>
                </div>
                <a href="profile.php" class="profile-nav-dropdown-option">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    View Profile
                </a>
                <a href="classes.php" class="profile-nav-dropdown-option">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Manage Classes
                </a>
                <a href="#" class="profile-nav-dropdown-option">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 01-3.46 0"></path></svg>
                    Notification Settings
                </a>
                <a href="change_password.php" class="profile-nav-dropdown-option">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"></path></svg>
                    Settings
                </a>
                <a href="../auth/logout.php" class="profile-nav-dropdown-option profile-nav-logout logout-confirm-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    Log out
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- Create Post Modal -->
<div class="modal-overlay" id="createPostModal">
    <div class="modal-box modal-box-clean">
        <div class="modal-header modal-header-centered">
            <h2>Create post</h2>
            <button class="modal-close-btn" id="closeCreatePostModal">&times;</button>
        </div>
        <form id="createPostForm" enctype="multipart/form-data">
            <input type="text" id="postTitle" name="title" class="clean-field" placeholder="Title" maxlength="200" required>
            <textarea id="postContent" name="content" class="clean-field clean-textarea" placeholder="Content" maxlength="2000" required></textarea>

            <select id="postCourse" name="target_course_id" class="clean-field">
                <option value="">All courses</option>
            </select>

            <div class="clean-field-row">
                <select id="postYearLevel" name="target_year_level" class="clean-field">
                    <option value="">Year</option>
                    <option value="1">1st Year</option>
                    <option value="2">2nd Year</option>
                    <option value="3">3rd Year</option>
                    <option value="4">4th Year</option>
                </select>
                <input type="text" id="postSection" name="target_section_label" class="clean-field" placeholder="Section: Ex. 1-1">
            </div>

            <div class="clean-upload-wrap">
                <label for="postImages" class="clean-upload-box" id="cleanUploadBox">
                    <svg viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                </label>
                <input type="file" id="postImages" name="images[]" accept="image/*" multiple style="display:none;">
                <div id="imagePreviewRow" class="clean-image-preview-row"></div>
            </div>

            <label for="postVideo" class="clean-attach-link">+ Attach a video (MP4, WEBM, MOV)</label>
            <input type="file" id="postVideo" name="video" accept="video/mp4,video/webm,video/quicktime,video/x-msvideo" style="display:none;">
            <div id="videoPreviewRow" class="clean-attachment-preview"></div>

            <label for="postAttachment" class="clean-attach-link">+ Attach a file (PDF, DOCX, PPTX...)</label>
            <input type="file" id="postAttachment" name="attachment" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.csv,.zip,.rar,.rtf,.odt" style="display:none;">
            <div id="attachmentPreviewRow" class="clean-attachment-preview"></div>

            <button type="submit" class="clean-post-btn">POST</button>
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