<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$currentUserId = $_SESSION['user_id'];
$currentRole = $_SESSION['role'];

// Supports both the legacy param name (professor_id, used by student flow) and the new one (other_user_id, used by professor flow)
$otherUserId = (int)($_POST['other_user_id'] ?? $_POST['professor_id'] ?? 0);

if (!$otherUserId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing target user']);
    exit;
}

if ($currentRole === 'student') {
    $student_id = $currentUserId;
    $professor_id = $otherUserId;
} elseif ($currentRole === 'professor') {
    $professor_id = $currentUserId;
    $student_id = $otherUserId;
} else {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only students and professors can message each other']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM conversations WHERE student_id = ? AND professor_id = ?");
$stmt->execute([$student_id, $professor_id]);
$conv = $stmt->fetch();

if (!$conv) {
    $stmt = $pdo->prepare("INSERT INTO conversations (student_id, professor_id) VALUES (?, ?)");
    $stmt->execute([$student_id, $professor_id]);
    $conversation_id = $pdo->lastInsertId();
} else {
    $conversation_id = $conv['id'];
}

echo json_encode(['success' => true, 'conversation_id' => $conversation_id]);