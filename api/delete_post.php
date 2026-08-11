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

$professor_id = $_SESSION['user_id'];
$announcement_id = (int)($_POST['id'] ?? 0);

if (!$announcement_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing post ID.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id FROM announcements WHERE id = ? AND professor_id = ?");
    $stmt->execute([$announcement_id, $professor_id]);

    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Post not found or you do not have permission to delete it.']);
        exit;
    }

    // Related rows (images, comments, comment likes, post likes, notifications)
    // are removed automatically via ON DELETE CASCADE already set on those tables.
    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ? AND professor_id = ?");
    $stmt->execute([$announcement_id, $professor_id]);

    echo json_encode(['success' => true, 'message' => 'Announcement deleted successfully.']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete post.']);
}