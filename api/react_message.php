<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$message_id = (int)($_POST['message_id'] ?? 0);
$reaction = trim($_POST['reaction'] ?? '');
$allowedReactions = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];

if (!$message_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid message']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT m.id FROM messages m
    JOIN conversations c ON c.id = m.conversation_id
    WHERE m.id = ? AND (c.student_id = ? OR c.professor_id = ?)
");
$stmt->execute([$message_id, $user_id, $user_id]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($reaction === 'none') {
    $stmt = $pdo->prepare("DELETE FROM message_reactions WHERE message_id = ? AND user_id = ?");
    $stmt->execute([$message_id, $user_id]);
} else {
    if (!in_array($reaction, $allowedReactions, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid reaction']);
        exit;
    }
    $stmt = $pdo->prepare("
        INSERT INTO message_reactions (message_id, user_id, reaction)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE reaction = VALUES(reaction), created_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$message_id, $user_id, $reaction]);
}

$stmt = $pdo->prepare("SELECT reaction, user_id FROM message_reactions WHERE message_id = ?");
$stmt->execute([$message_id]);
$rows = $stmt->fetchAll();

$counts = [];
$myReaction = null;
foreach ($rows as $r) {
    $counts[$r['reaction']] = ($counts[$r['reaction']] ?? 0) + 1;
    if ((int)$r['user_id'] === $user_id) {
        $myReaction = $r['reaction'];
    }
}

echo json_encode(['success' => true, 'counts' => $counts, 'my_reaction' => $myReaction]);