<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('student');

$currentPage = 'home';
$student_id = $_SESSION['user_id'];
$highlightId = isset($_GET['highlight']) ? (int)$_GET['highlight'] : null;
$highlightCommentId = isset($_GET['comment']) ? (int)$_GET['comment'] : null;

$stmt = $pdo->prepare("SELECT course_id, section_label, year_level FROM students WHERE user_id = ?");
$stmt->execute([$student_id]);
$studentInfo = $stmt->fetch();

$isRestricted = is_student_restricted($pdo, $student_id);

$stmt = $pdo->prepare("
    SELECT a.id, a.professor_id, a.title, a.content, a.attachment_path, a.created_at, a.updated_at,
           u.first_name, u.last_name, u.profile_picture, u.role AS author_role, p.department,
           s2.is_mayor,
           (SELECT COUNT(*) FROM announcement_likes WHERE announcement_id = a.id) AS like_count,
           (SELECT COUNT(*) FROM announcement_comments WHERE announcement_id = a.id) AS comment_count,
           (SELECT COUNT(*) FROM announcement_likes WHERE announcement_id = a.id AND student_id = ?) AS user_liked
    FROM announcements a
    JOIN users u ON a.professor_id = u.id
    LEFT JOIN professors p ON p.user_id = u.id
    LEFT JOIN students s2 ON s2.user_id = u.id
    WHERE (a.target_course_id IS NULL OR a.target_course_id = ?)
      AND (a.target_year_level IS NULL OR a.target_year_level = ?)
      AND (a.target_section_label IS NULL OR a.target_section_label = ?)
    ORDER BY a.created_at DESC
");
if (!$isRestricted) {
    $stmt->execute([$student_id, $studentInfo['course_id'], $studentInfo['year_level'], $studentInfo['section_label']]);
    $posts = $stmt->fetchAll();
} else {
    $posts = [];
}

// Fetch images for all posts in one query
$postImages = [];
if (!empty($posts)) {
    $postIds = array_column($posts, 'id');
    $placeholders = implode(',', array_fill(0, count($postIds), '?'));
    $imgStmt = $pdo->prepare("SELECT announcement_id, image_path FROM announcement_images WHERE announcement_id IN ($placeholders) ORDER BY display_order ASC");
    $imgStmt->execute($postIds);
    foreach ($imgStmt->fetchAll() as $img) {
        $postImages[$img['announcement_id']][] = $img['image_path'];
    }
}

function time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 0) $diff = 0; // clock drift safety net — never show a negative "future" time
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - CDSGA HUB</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=2">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-page">

    <?php include __DIR__ . '/../includes/student_nav.php'; ?>

    <div class="dashboard-layout">
        <div class="dashboard-side-column">
            <div class="quick-links-card">
                <div class="quick-link-wrapper">
                    <button type="button" class="quick-link-item" id="assignmentsWidgetToggle">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path></svg>
                        My Assignments
                    </button>
                    <div class="assignments-widget" id="assignmentsWidget">
                        <div class="assignments-widget-header">
                            <div class="assignments-widget-title">My Assignments</div>
                            <div class="assignments-widget-subtitle">Assignments from your professors this semester</div>
                        </div>
                        <div class="assignments-widget-list" id="assignmentsWidgetList">
                            <div class="classes-empty">Loading...</div>
                        </div>
                        <a href="assignments.php" class="class-overview-view-all" style="margin:0 16px 14px;">View All Assignments</a>
                    </div>
                </div>

                <div class="quick-link-wrapper">
                    <button type="button" class="quick-link-item" id="classmatesWidgetToggle">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 00-3-3.87"></path><path d="M16 3.13a4 4 0 010 7.75"></path></svg>
                        Classmates
                    </button>
                </div>
            </div>

            <div class="classmates-widget" id="classmatesWidget">
                <div class="classmates-widget-header">
                    <div>
                        <div class="classmates-widget-title">Classmates</div>
                        <div class="classmates-widget-subtitle">Students in your section this semester</div>
                    </div>
                    <button type="button" class="modal-close-btn" id="closeClassmatesWidget">&times;</button>
                </div>
                <div class="directory-list" id="classmatesWidgetList">
                    <div class="directory-empty">Loading...</div>
                </div>
            </div>

            <div class="verse-pin-wrap">
                <div class="verse-pin-card" id="verseCard">
                    <div class="verse-pin-dot"></div>
                    <div class="verse-pin-image" style="background-image: url('../assets/images/daily-verse-bg.jpg')"></div>
                    <div class="verse-pin-text">&ldquo;<?= sanitize($dailyVerse['text']) ?>&rdquo;</div>
                    <div class="verse-pin-ref">&mdash; <?= sanitize($dailyVerse['reference']) ?></div>
                </div>
            </div>

            <div class="upcoming-card" id="upcomingCard" style="display:none;">
                <div class="upcoming-card-title">Upcomming</div>
                <div class="upcoming-list" id="upcomingList"></div>
            </div>

            <div class="live-now-card" id="liveNowCard" style="display:none;"></div>
        </div>

        <div class="feed-container">
        <?php if ($isRestricted): ?>
            <div class="empty-state restricted-state">
                🚫 Your account is currently restricted due to 3 warnings.<br>
                Please coordinate with OSAS (Office of Student Affairs and Services).
            </div>
        <?php elseif (empty($posts)): ?>
            <div class="empty-state">
                No announcements yet. Check back later!
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
<div class="post-card" id="post-<?= $post['id'] ?>" data-post-id="<?= $post['id'] ?>">
                    <div class="post-header">
                        <div class="avatar-circle" data-profile-user-id="<?= $post['professor_id'] ?>"<?php if (!empty($post['profile_picture'])): ?> style="background-image:url('../<?= sanitize($post['profile_picture']) ?>')"<?php endif; ?>></div>
                        <a href="messages.php?professor_id=<?= $post['professor_id'] ?? '' ?>" data-profile-user-id="<?= $post['professor_id'] ?>" style="text-decoration:none;">
                            <div class="post-author-name">
                                <?= $post['author_role'] === 'professor' ? 'Prof. ' : '' ?><?= sanitize($post['first_name'] . ' ' . $post['last_name']) ?>
                                <?php if (!empty($post['is_mayor'])): ?><span class="post-mayor-tag">Mayor</span><?php endif; ?>
                            </div>
                            <div class="post-author-dept"><?= sanitize($post['author_role'] === 'professor' ? ($post['department'] ?? '') : 'Student') ?></div>
                        </a>
                        <div class="post-time">
                            <?= time_ago($post['created_at']) ?>
                            <?php if (!empty($post['updated_at'])): ?>
                                · Edited <?= time_ago($post['updated_at']) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="post-title"><?= sanitize($post['title']) ?></div>
                    <div class="post-content"><?= nl2br(sanitize($post['content'])) ?></div>

                    <?php if (!empty($postImages[$post['id']])): ?>
                        <?php $imgCount = count($postImages[$post['id']]); ?>
                        <div class="post-images-grid <?= $imgCount === 1 ? 'single-image' : 'two-images' ?>">
                            <?php foreach ($postImages[$post['id']] as $imgPath): ?>
                                <img src="../<?= sanitize($imgPath) ?>" alt="Announcement image">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($post['attachment_path'])): ?>
                        <?php $att = get_attachment_meta($post['attachment_path']); ?>
                        <a class="post-attachment-card" href="../<?= sanitize($post['attachment_path']) ?>" download>
                            <div class="post-attachment-icon" style="background-color:<?= $att['color'] ?>"><?= $att['label'] ?></div>
                            <div class="post-attachment-info">
                                <div class="post-attachment-name"><?= sanitize($att['filename']) ?></div>
                                <div class="post-attachment-size"><?= $att['size'] ?></div>
                            </div>
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        </a>
                    <?php endif; ?>

                    <div class="post-actions">
                        <button class="action-btn like-btn <?= $post['user_liked'] ? 'liked' : '' ?>" data-id="<?= $post['id'] ?>">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.6l-1-1a5.5 5.5 0 00-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 000-7.8z"></path>
                            </svg>
                            <span class="like-count"><?= (int)$post['like_count'] ?></span>
                        </button>
                        <button class="action-btn comment-toggle-btn" data-id="<?= $post['id'] ?>">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 11.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 014.7-7.6 8.38 8.38 0 013.8-.9h.5a8.48 8.48 0 018 8v.5z"></path>
                            </svg>
                            <span id="comment-count-<?= $post['id'] ?>"><?= (int)$post['comment_count'] ?></span>
                        </button>
                    </div>

                    <div class="comment-section" id="comments-<?= $post['id'] ?>">
                        <div class="comment-list" id="comment-list-<?= $post['id'] ?>"></div>
                        <form class="comment-input-row comment-form" data-id="<?= $post['id'] ?>">
                            <input type="text" class="comment-text-input" placeholder="Write a comment..." maxlength="500" required>
                            <button type="submit" class="comment-send-btn">
                                <svg viewBox="0 0 24 24"><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="scroll-hint" id="scrollHint" style="display:none;">Scroll for more ▾</div>
            <div class="scroll-end" id="scrollEnd" style="display:none;">No more posts</div>
        <?php endif; ?>
    </div>
    </div>

    <!-- Image Lightbox -->
    <div class="lightbox-overlay" id="lightboxOverlay">
        <button class="lightbox-close" id="lightboxClose" title="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
        <button class="lightbox-nav lightbox-prev" id="lightboxPrev" title="Previous">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </button>
        <div class="lightbox-stage" id="lightboxStage">
            <img src="" alt="Post image" id="lightboxImage">
        </div>
        <button class="lightbox-nav lightbox-next" id="lightboxNext" title="Next">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </button>
        <div class="lightbox-dots" id="lightboxDots"></div>
    </div>

    <script>
        const HIGHLIGHT_TARGET = <?= $highlightId ? (int)$highlightId : 'null' ?>;
        const HIGHLIGHT_TYPE = '<?= isset($_GET['type']) ? sanitize($_GET['type']) : '' ?>';
        const HIGHLIGHT_COMMENT_ID = <?= $highlightCommentId ? (int)$highlightCommentId : 'null' ?>;
    </script>
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/warning_policy.js"></script>
    <script src="../assets/js/mayor_create.js"></script>
    <script src="../assets/js/upcoming_widget_student.js"></script>
    <script src="../assets/js/profile_card.js"></script>
    <script src="../assets/js/message_widget.js"></script>
    <script src="../assets/js/quick_widgets.js"></script>
</body>

</html>