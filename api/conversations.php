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
$role = $_SESSION['role'];

if ($role === 'student') {
    $stmt = $pdo->prepare("
        SELECT c.id AS conversation_id, u.id AS other_user_id,
               u.first_name, u.last_name, u.profile_picture,
               (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message,
               (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_time,
               (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_id != ? AND is_read = 0) AS unread_count
        FROM conversations c
        JOIN users u ON u.id = c.professor_id
        WHERE c.student_id = ?
        ORDER BY last_time DESC
    ");
    $stmt->execute([$user_id, $user_id]);
} else {
    $stmt = $pdo->prepare("
        SELECT c.id AS conversation_id, u.id AS other_user_id,
               u.first_name, u.last_name, u.profile_picture,
               (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_message,
               (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) AS last_time,
               (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_id != ? AND is_read = 0) AS unread_count
        FROM conversations c
        JOIN users u ON u.id = c.student_id
        WHERE c.professor_id = ?
        ORDER BY last_time DESC
    ");
    $stmt->execute([$user_id, $user_id]);
}

$conversations = $stmt->fetchAll();
echo json_encode(['success' => true, 'conversations' => $conversations]);