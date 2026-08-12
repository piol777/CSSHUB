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

$student_id = (int)($_POST['student_id'] ?? 0);
$grant = ($_POST['grant'] ?? '') === '1';

$stmt = $pdo->prepare("SELECT user_id FROM students WHERE user_id = ?");
$stmt->execute([$student_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Student not found.']);
    exit;
}

$stmt = $pdo->prepare("UPDATE students SET is_mayor = ? WHERE user_id = ?");
$stmt->execute([$grant ? 1 : 0, $student_id]);

echo json_encode(['success' => true, 'is_mayor' => $grant]);