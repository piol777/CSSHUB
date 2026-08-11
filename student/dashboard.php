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

$stmt = $pdo->prepare("
    SELECT a.id, a.professor_id, a.title, a.content, a.created_at,
           u.first_name, u.last_name, u.profile_picture, p.department,
           (SELECT COUNT(*) FROM announcement_likes WHERE announcement_id = a.id) AS like_count,
           (SELECT COUNT(*) FROM announcement_comments WHERE announcement_id = a.id) AS comment_count,
           (SELECT COUNT(*) FROM announcement_likes WHERE announcement_id = a.id AND student_id = ?) AS user_liked
    FROM announcements a
    JOIN users u ON a.professor_id = u.id
    LEFT JOIN professors p ON p.user_id = u.id
    WHERE (a.target_course_id IS NULL OR a.target_course_id = ?)
      AND (a.target_year_level IS NULL OR a.target_year_level = ?)
      AND (a.target_section_label IS NULL OR a.target_section_label = ?)
    ORDER BY a.created_at DESC
");
$stmt->execute([$student_id, $studentInfo['course_id'], $studentInfo['year_level'], $studentInfo['section_label']]);
$posts = $stmt->fetchAll();

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
    <title>Home - CSS HUB</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=2">
</head>
<body class="dashboard-page">

    <?php include __DIR__ . '/../includes/student_nav.php'; ?>

    <div class="dashboard-layout">
        <div class="dashboard-side-column">
            <div class="upcoming-card">
                <div class="upcoming-card-title">Upcomming</div>
                <div class="upcoming-list" id="upcomingList">
                    <div class="upcoming-empty">Loading...</div>
                </div>
            </div>

            <div class="live-now-card" id="liveNowCard">
                <div class="live-now-empty">Loading...</div>
            </div>
        </div>

        <div class="feed-container">
        <?php if (empty($posts)): ?>
            <div class="empty-state">
                No announcements yet. Check back later!
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
<div class="post-card" id="post-<?= $post['id'] ?>" data-post-id="<?= $post['id'] ?>">
                    <div class="post-header">
                        <div class="avatar-circle" data-profile-user-id="<?= $post['professor_id'] ?>"<?php if (!empty($post['profile_picture'])): ?> style="background-image:url('../<?= sanitize($post['profile_picture']) ?>')"<?php endif; ?>></div>
                        <a href="messages.php?professor_id=<?= $post['professor_id'] ?? '' ?>" data-profile-user-id="<?= $post['professor_id'] ?>" style="text-decoration:none;">
                            <div class="post-author-name">Prof. <?= sanitize($post['first_name'] . ' ' . $post['last_name']) ?></div>
                            <div class="post-author-dept"><?= sanitize($post['department'] ?? '') ?></div>
                        </a>
                        <div class="post-time"><?= time_ago($post['created_at']) ?></div>
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

            <?php if (count($posts) > 3): ?>
                <div class="scroll-hint">Scroll for more ▾</div>
            <?php endif; ?>
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
    <script src="../assets/js/upcoming_widget_student.js"></script>
    <script src="../assets/js/profile_card.js"></script>
    <script src="../assets/js/message_widget.js"></script>
    <script src="../assets/js/profile_card.js"></script>
    
</body>
</html>