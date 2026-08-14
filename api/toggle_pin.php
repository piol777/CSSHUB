<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$post_id = (int)($_POST['id'] ?? 0);

$stmt = $pdo->prepare("SELECT is_pinned FROM announcements WHERE id = ? AND professor_id = ?");
$stmt->execute([$post_id, $_SESSION['user_id']]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Post not found.']);
    exit;
}

$newState = $post['is_pinned'] ? 0 : 1;
$stmt = $pdo->prepare("UPDATE announcements SET is_pinned = ? WHERE id = ?");
$stmt->execute([$newState, $post_id]);

echo json_encode(['success' => true, 'is_pinned' => (bool)$newState]);