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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT n.id, n.type, n.message, n.is_read, n.created_at, n.announcement_id, n.comment_id,
               u.first_name, u.last_name, u.profile_picture
        FROM notifications n
        JOIN users u ON n.actor_id = u.id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $unreadCount = $stmt->fetch()['total'];

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => (int)$unreadCount
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Invalid method']);