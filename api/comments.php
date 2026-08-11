<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $announcement_id = (int)($_GET['announcement_id'] ?? 0);

    if (!$announcement_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing announcement ID']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT c.id, c.content, c.created_at, c.student_id AS commenter_id,
               u.first_name, u.last_name, u.role AS commenter_role, u.profile_picture,
               (SELECT COUNT(*) FROM comment_likes WHERE comment_id = c.id) AS like_count,
               (SELECT COUNT(*) FROM comment_likes WHERE comment_id = c.id AND user_id = ?) AS user_liked
        FROM announcement_comments c
        JOIN users u ON c.student_id = u.id
        WHERE c.announcement_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$user_id, $announcement_id]);
    $comments = $stmt->fetchAll();

    echo json_encode(['success' => true, 'comments' => $comments]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $announcement_id = (int)($_POST['announcement_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    if (!$announcement_id || $content === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
        exit;
    }

    if (strlen($content) > 500) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Comment is too long']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, professor_id FROM announcements WHERE id = ?");
    $stmt->execute([$announcement_id]);
    $announcement = $stmt->fetch();

    if (!$announcement) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Announcement not found']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO announcement_comments (announcement_id, student_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$announcement_id, $user_id, $content]);
    $new_comment_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT first_name, last_name, profile_picture FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($role === 'student') {
        // Notify the professor who owns the post
        $message = $user['first_name'] . ' ' . $user['last_name'] . ' commented on your announcement';
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, actor_id, announcement_id, comment_id, message)
            VALUES (?, 'comment', ?, ?, ?, ?)
        ");
        $stmt->execute([$announcement['professor_id'], $user_id, $announcement_id, $new_comment_id, $message]);
    } else {
        // Professor replied — notify all students who previously commented on this post (excluding the professor)
        $stmt = $pdo->prepare("
            SELECT DISTINCT student_id FROM announcement_comments
            WHERE announcement_id = ? AND student_id != ?
        ");
        $stmt->execute([$announcement_id, $user_id]);
        $priorCommenters = $stmt->fetchAll();

        $message = 'Prof. ' . $user['first_name'] . ' ' . $user['last_name'] . ' replied on an announcement you commented on';
        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, actor_id, announcement_id, comment_id, message)
            VALUES (?, 'comment', ?, ?, ?, ?)
        ");
        foreach ($priorCommenters as $commenter) {
            $notifStmt->execute([$commenter['student_id'], $user_id, $announcement_id, $new_comment_id, $message]);
        }
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM announcement_comments WHERE announcement_id = ?");
    $stmt->execute([$announcement_id]);
    $count = $stmt->fetch()['total'];

    echo json_encode([
        'success' => true,
        'comment' => [
            'id' => $new_comment_id,
            'commenter_id' => $user_id,
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'commenter_role' => $role,
            'profile_picture' => $user['profile_picture'],
            'content' => $content,
            'created_at' => date('Y-m-d H:i:s')
        ],
        'comment_count' => (int)$count
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Invalid method']);