<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$student_id = $_SESSION['user_id'];
$announcement_id = (int)($_POST['announcement_id'] ?? 0);

if (!$announcement_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing announcement ID']);
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

$stmt = $pdo->prepare("SELECT id FROM announcement_likes WHERE announcement_id = ? AND student_id = ?");
$stmt->execute([$announcement_id, $student_id]);
$existingLike = $stmt->fetch();

if ($existingLike) {
    $stmt = $pdo->prepare("DELETE FROM announcement_likes WHERE id = ?");
    $stmt->execute([$existingLike['id']]);
    $liked = false;
} else {
    $stmt = $pdo->prepare("INSERT INTO announcement_likes (announcement_id, student_id) VALUES (?, ?)");
    $stmt->execute([$announcement_id, $student_id]);
    $liked = true;

    // Notify the professor who owns this post (only when liking, not unliking)
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$student_id]);
    $liker = $stmt->fetch();

    $message = $liker['first_name'] . ' ' . $liker['last_name'] . ' liked your announcement';
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, actor_id, announcement_id, message)
        VALUES (?, 'like', ?, ?, ?)
    ");
    $stmt->execute([$announcement['professor_id'], $student_id, $announcement_id, $message]);
}

$stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM announcement_likes WHERE announcement_id = ?");
$stmt->execute([$announcement_id]);
$count = $stmt->fetch()['total'];

echo json_encode([
    'success' => true,
    'liked' => $liked,
    'like_count' => (int)$count
]);