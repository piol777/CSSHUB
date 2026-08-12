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
$reason = trim($_POST['reason'] ?? '');

if ($student_id <= 0 || $reason === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Reason is required.']);
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE user_id = ?");
$stmt->execute([$student_id]);
if (!$stmt->fetchColumn()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Student not found.']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO student_warnings (student_id, professor_id, reason) VALUES (?, ?, ?)");
$stmt->execute([$student_id, $_SESSION['user_id'], $reason]);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM student_warnings WHERE student_id = ?");
$countStmt->execute([$student_id]);
$count = (int)$countStmt->fetchColumn();

echo json_encode(['success' => true, 'warning_count' => $count]);