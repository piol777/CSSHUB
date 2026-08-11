<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM messages m
    JOIN conversations c ON m.conversation_id = c.id
    WHERE (c.student_id = ? OR c.professor_id = ?)
      AND m.sender_id != ?
      AND m.is_read = 0
");
$stmt->execute([$user_id, $user_id, $user_id]);
$count = $stmt->fetch()['total'];

echo json_encode(['success' => true, 'unread_count' => (int)$count]);