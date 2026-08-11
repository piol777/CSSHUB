<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);

echo json_encode(['success' => true]);