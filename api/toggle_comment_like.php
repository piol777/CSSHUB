<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$comment_id = (int)($_POST['comment_id'] ?? 0);

if (!$comment_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing comment ID']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
$stmt->execute([$comment_id, $user_id]);
$existing = $stmt->fetch();

if ($existing) {
    $stmt = $pdo->prepare("DELETE FROM comment_likes WHERE id = ?");
    $stmt->execute([$existing['id']]);
    $liked = false;
} else {
    $stmt = $pdo->prepare("INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)");
    $stmt->execute([$comment_id, $user_id]);
    $liked = true;
}

$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM comment_likes WHERE comment_id = ?");
$stmt->execute([$comment_id]);
$count = $stmt->fetch()['total'];

echo json_encode(['success' => true, 'liked' => $liked, 'like_count' => (int)$count]);