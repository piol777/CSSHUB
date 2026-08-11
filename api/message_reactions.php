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
$reaction = $_POST['reaction'] ?? '';

$allowedReactions = ['like', 'love', 'haha', 'wow', 'sad', 'angry'];

if (!$message_id || !in_array($reaction, $allowedReactions, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Make sure this message belongs to a conversation the user is part of
$stmt = $pdo->prepare("
    SELECT m.id, m.conversation_id
    FROM messages m
    INNER JOIN conversations c ON c.id = m.conversation_id
    WHERE m.id = ? AND (c.student_id = ? OR c.professor_id = ?)
");
$stmt->execute([$message_id, $user_id, $user_id]);
$message = $stmt->fetch();

if (!$message) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if this user already reacted to this message
$stmt = $pdo->prepare("SELECT reaction FROM message_reactions WHERE message_id = ? AND user_id = ?");
$stmt->execute([$message_id, $user_id]);
$existing = $stmt->fetch();

if ($existing && $existing['reaction'] === $reaction) {
    // Tapping the same reaction again removes it (Messenger-style toggle)
    $stmt = $pdo->prepare("DELETE FROM message_reactions WHERE message_id = ? AND user_id = ?");
    $stmt->execute([$message_id, $user_id]);
    $myReaction = null;
} else {
    // Insert new, or replace the user's previous reaction on this message
    $stmt = $pdo->prepare("
        INSERT INTO message_reactions (message_id, user_id, reaction)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE reaction = VALUES(reaction)
    ");
    $stmt->execute([$message_id, $user_id, $reaction]);
    $myReaction = $reaction;
}

// Return the fresh reaction list for this message so the frontend can re-render it
$stmt = $pdo->prepare("SELECT user_id, reaction FROM message_reactions WHERE message_id = ?");
$stmt->execute([$message_id]);
$allReactions = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'message_id' => $message_id,
    'my_reaction' => $myReaction,
    'reactions' => $allReactions
]);