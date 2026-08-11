<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('professor');

$currentPage = 'home';
$professor_id = $_SESSION['user_id'];
$highlightId = isset($_GET['highlight']) ? (int)$_GET['highlight'] : null;

$stmt = $pdo->prepare("
    SELECT a.id, a.title, a.content, a.created_at,
           u.first_name, u.last_name, u.profile_picture, p.department,
           (SELECT COUNT(*) FROM announcement_likes WHERE announcement_id = a.id) AS like_count,
           (SELECT COUNT(*) FROM announcement_comments WHERE announcement_id = a.id) AS comment_count
    FROM announcements a
    JOIN users u ON a.professor_id = u.id
    LEFT JOIN professors p ON p.user_id = u.id
    WHERE a.professor_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$professor_id]);
$posts = $stmt->fetchAll();

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
    <title>Home - CDSGA HUB</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=2">
</head>
<body class="dashboard-page">

    <?php include __DIR__ . '/../includes/professor_nav.php'; ?>

    <div class="feed-container">
        <div class="upcoming-composer-bar">
            <button type="button" class="upcoming-composer-icon-btn" id="upcomingComposerClassBtn" title="Post a class schedule">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="M21 15l-5-5L5 21"></path></svg>
            </button>
            <button type="button" class="upcoming-composer-icon-btn" id="upcomingComposerVideoBtn" title="Post a live class">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2"></rect></svg>
            </button>
            <div class="upcoming-composer-input" id="openCreateUpcomingModal">Post upcoming here..</div>
            <div class="avatar-circle upcoming-composer-avatar" id="openCreateUpcomingModalAvatar"<?php if ($navProfilePic): ?> style="background-image:url('../<?= sanitize($navProfilePic) ?>')"<?php endif; ?>></div>
        </div>

        <?php if (empty($posts)): ?>
            <div class="empty-state">
                You haven't posted anything yet. Click the + icon above to create your first announcement.
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <div class="post-card" id="post-<?= $post['id'] ?>" data-post-id="<?= $post['id'] ?>">
                    <div class="post-header">
                        <div class="avatar-circle" data-profile-user-id="<?= $professor_id ?>"<?php if (!empty($post['profile_picture'])): ?> style="background-image:url('../<?= sanitize($post['profile_picture']) ?>')"<?php endif; ?>></div>
                        <div data-profile-user-id="<?= $professor_id ?>">
                            <div class="post-author-name">Prof. <?= sanitize($post['first_name'] . ' ' . $post['last_name']) ?></div>
                            <div class="post-author-dept"><?= sanitize($post['department'] ?? '') ?></div>
                        </div>
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
                        <span class="action-btn" style="cursor:default;">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.6l-1-1a5.5 5.5 0 00-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 000-7.8z"></path>
                            </svg>
                            <span><?= (int)$post['like_count'] ?></span>
                        </span>
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
                            <input type="text" class="comment-text-input" placeholder="Write a reply..." maxlength="500" required>
                            <button type="submit" class="comment-send-btn">
                                <svg viewBox="0 0 24 24"><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        const HIGHLIGHT_TARGET = <?= $highlightId ? (int)$highlightId : 'null' ?>;
        const HIGHLIGHT_TYPE = '<?= isset($_GET['type']) ? sanitize($_GET['type']) : '' ?>';
    </script>
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/create_post.js"></script>
    <script src="../assets/js/upcoming_composer.js"></script>
    <script src="../assets/js/message_widget.js"></script>
    <script src="../assets/js/directory_widget.js"></script>
    <script src="../assets/js/profile_card.js"></script>
</body>
</html>