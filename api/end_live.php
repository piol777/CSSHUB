<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'professor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$roomId = $_POST['room_id'] ?? '';
if ($roomId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing room_id']);
    exit;
}

$stmt = $pdo->prepare("UPDATE live_sessions SET status = 'ended', ended_at = NOW() WHERE room_id = ? AND professor_id = ?");
$stmt->execute([$roomId, $_SESSION['user_id']]);

echo json_encode(['success' => true]);