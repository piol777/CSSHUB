<?php
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../config/database.php';
guard_role('professor');

$currentPage = 'home';
$professor_id = $_SESSION['user_id'];
$highlightId = isset($_GET['highlight']) ? (int)$_GET['highlight'] : null;

$classStmt = $pdo->prepare("
    SELECT
        cs.id, cs.subject_name, cs.section_label, cs.year_level, cs.semester_label, cs.color_hex,
        c.code AS course_code,
        (SELECT COUNT(*) FROM students s
            WHERE s.course_id = cs.course_id AND s.year_level = cs.year_level AND s.section_label = cs.section_label
        ) AS students_count,
        (SELECT COUNT(DISTINCT a.id) FROM assignments a
            WHERE a.class_section_id = cs.id
            AND EXISTS (
                SELECT 1 FROM students s2
                WHERE s2.course_id = cs.course_id AND s2.year_level = cs.year_level AND s2.section_label = cs.section_label
                AND NOT EXISTS (
                    SELECT 1 FROM assignment_submissions sub
                    WHERE sub.assignment_id = a.id AND sub.student_id = s2.user_id AND sub.status = 'graded'
                )
            )
        ) AS assignments_pending,
        (SELECT ROUND(AVG(sub.grade), 0) FROM assignment_submissions sub
            JOIN assignments a2 ON a2.id = sub.assignment_id
            WHERE a2.class_section_id = cs.id AND sub.status = 'graded'
        ) AS grade_average,
        (SELECT ROUND(AVG(CASE WHEN ar.status = 'present' THEN 100 ELSE 0 END), 0)
            FROM attendance_records ar
            JOIN attendance_sessions ats ON ats.id = ar.session_id
            WHERE ats.class_section_id = cs.id
        ) AS attendance_pct
    FROM class_sections cs
    JOIN courses c ON c.id = cs.course_id
    WHERE cs.professor_id = ?
    ORDER BY cs.created_at DESC
");
$classStmt->execute([$professor_id]);
$classOverview = $classStmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT a.id, a.title, a.content, a.created_at, a.updated_at,
           a.target_course_id, a.target_year_level, a.target_section_label,
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
    <link rel="stylesheet" href="../assets/css/classes.css?v=1">
</head>
<body class="dashboard-page">

    <?php include __DIR__ . '/../includes/professor_nav.php'; ?>

    <div class="dashboard-side-column">
        <div class="class-overview-card">
            <div class="class-overview-header">
                <h3>Class Overview</h3>
                <p>Overview of your classes this semester</p>
            </div>

            <?php if (empty($classOverview)): ?>
                <div class="classes-empty">No classes yet. <a href="classes.php">Add one</a>.</div>
            <?php else: ?>
                <div class="class-overview-semester"><?= sanitize($classOverview[0]['semester_label']) ?></div>

                <?php foreach (array_slice($classOverview, 0, 3) as $cls): ?>
                    <?php
                        $attendance = $cls['attendance_pct'] !== null ? $cls['attendance_pct'] . '%' : '—';
                        $gradeAvg = $cls['grade_average'] !== null ? $cls['grade_average'] . '%' : '—';
                    ?>
                    <div class="class-card" style="border-left-color:<?= sanitize($cls['color_hex']) ?>">
                        <div class="class-card-top">
                            <div class="class-card-icon" style="background:<?= sanitize($cls['color_hex']) ?>">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                            </div>
                            <div>
                                <div class="class-card-title"><?= sanitize($cls['course_code']) ?> <?= (int)$cls['year_level'] ?>-<?= sanitize($cls['section_label']) ?></div>
                                <div class="class-card-subject"><?= sanitize($cls['subject_name']) ?></div>
                            </div>
                        </div>
                        <div class="class-card-stats">
                            <div class="class-stat"><span class="class-stat-label">Students</span><span class="class-stat-value"><?= (int)$cls['students_count'] ?></span></div>
                            <div class="class-stat"><span class="class-stat-label">Attendance</span><span class="class-stat-value"><?= $attendance ?></span></div>
                            <div class="class-stat"><span class="class-stat-label">Assignments</span><span class="class-stat-value"><?= (int)$cls['assignments_pending'] ?> Pending</span></div>
                            <div class="class-stat"><span class="class-stat-label">Grades</span><span class="class-stat-value"><?= $gradeAvg ?></span></div>
                        </div>
                        <a href="class_details.php?id=<?= (int)$cls['id'] ?>" class="class-view-details-btn">View Class Details &rarr;</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <a href="classes.php" class="class-overview-view-all">View All Classes</a>
        </div>
    </div>

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
                <div class="post-card"
                     id="post-<?= $post['id'] ?>"
                     data-post-id="<?= $post['id'] ?>"
                     data-post-title="<?= sanitize($post['title']) ?>"
                     data-post-content="<?= sanitize($post['content']) ?>"
                     data-course-id="<?= $post['target_course_id'] ?? '' ?>"
                     data-year-level="<?= $post['target_year_level'] ?? '' ?>"
                     data-section="<?= sanitize($post['target_section_label'] ?? '') ?>">
                    <div class="post-header">
                        <div class="avatar-circle" data-profile-user-id="<?= $professor_id ?>"<?php if (!empty($post['profile_picture'])): ?> style="background-image:url('../<?= sanitize($post['profile_picture']) ?>')"<?php endif; ?>></div>
                        <div data-profile-user-id="<?= $professor_id ?>">
                            <div class="post-author-name">Prof. <?= sanitize($post['first_name'] . ' ' . $post['last_name']) ?></div>
                            <div class="post-author-dept"><?= sanitize($post['department'] ?? '') ?></div>
                        </div>
                        <div class="post-time">
                            <?= time_ago($post['created_at']) ?>
                            <?php if (!empty($post['updated_at'])): ?>
                                · Edited <?= time_ago($post['updated_at']) ?>
                            <?php endif; ?>
                        </div>

                        <div class="post-menu-wrapper">
                            <button type="button" class="post-menu-btn" data-menu-toggle title="Post options">
                                <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="1.8"></circle><circle cx="12" cy="12" r="1.8"></circle><circle cx="12" cy="19" r="1.8"></circle></svg>
                            </button>
                            <div class="post-menu-dropdown">
                                <button type="button" class="post-menu-item post-edit-btn">Edit</button>
                                <button type="button" class="post-menu-item post-delete-btn danger">Delete</button>
                            </div>
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
    <script src="../assets/js/edit_post.js"></script>
    <script src="../assets/js/upcoming_composer.js"></script>
    <script src="../assets/js/message_widget.js"></script>
    <script src="../assets/js/directory_widget.js"></script>
    <script src="../assets/js/profile_card.js"></script>
</body>
</html>